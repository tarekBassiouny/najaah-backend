<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\CenterMismatchException;
use App\Models\Center;
use App\Models\User;
use App\Services\Centers\Contracts\CenterScopeServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenterRouteScope
{
    public function __construct(
        private readonly CenterScopeServiceInterface $centerScopeService
    ) {}

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $routeCenter = $request->route('center');
        if ($routeCenter === null) {
            return $next($request);
        }

        $centerId = $routeCenter instanceof Center
            ? (int) $routeCenter->id
            : (is_numeric($routeCenter) ? (int) $routeCenter : null);

        if ($centerId === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Resource does not belong to your center.',
                ],
            ], 403);
        }

        $resolvedCenterId = $this->resolveCenterId($request->attributes->get('resolved_center_id'));
        if ($resolvedCenterId !== null && $resolvedCenterId !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Resource does not belong to your center.',
                ],
            ], 403);
        }

        try {
            $this->centerScopeService->assertAdminCenterId($user, $centerId);
            $this->centerScopeService->assertResolvedApiCenterScope($user, $resolvedCenterId);
        } catch (CenterMismatchException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Resource does not belong to your center.',
                ],
            ], 403);
        }

        return $next($request);
    }

    private function resolveCenterId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
