<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Center;
use App\Models\User;
use App\Services\Settings\PolicySettingsService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures that guest browsing is allowed for the current center context.
 *
 * This middleware should be used after OptionalJwtMobileMiddleware.
 * It checks if the center allows guest browsing when the user is a guest.
 *
 * Rules:
 * - Authenticated users: Always allowed
 * - Guest + branded center (via subdomain): Check center's allow_guest_browsing setting
 * - Guest + unbranded context (Najaah app): Allowed by default
 * - Guest + specific center in route: Check that center's allow_guest_browsing setting
 */
class EnsureGuestBrowsingAllowed
{
    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Authenticated users are always allowed
        if ($user instanceof User) {
            return $next($request);
        }

        // System-level force-disable overrides all centers
        $constraints = $this->policySettingsService->systemConstraints();
        if ($constraints['force_disable_guest_browsing'] === true) {
            return $this->denyGuestAccess();
        }

        // Guest user - check if guest browsing is allowed
        $isGuest = (bool) $request->attributes->get('is_guest', true);

        if (! $isGuest) {
            return $next($request);
        }

        // Check for center in route parameters first
        $routeCenter = $request->route('center');

        if ($routeCenter instanceof Center) {
            if (! $this->policySettingsService->centerAllowsGuestBrowsing($routeCenter)) {
                return $this->denyGuestAccess();
            }

            return $next($request);
        }

        // Check for resolved center from subdomain
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        if (is_numeric($resolvedCenterId)) {
            $center = Center::find((int) $resolvedCenterId);

            if ($center instanceof Center && ! $this->policySettingsService->centerAllowsGuestBrowsing($center)) {
                return $this->denyGuestAccess();
            }
        }

        // Unbranded/Najaah app context with no specific center - allow by default
        return $next($request);
    }

    private function denyGuestAccess(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'GUEST_BROWSING_NOT_ALLOWED',
                'message' => 'Guest browsing is not enabled for this center. Please sign in to continue.',
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
