<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional JWT authentication middleware for mobile guest browsing.
 *
 * This middleware attempts to authenticate the user if a JWT token is present,
 * but allows the request to proceed without authentication for guest browsing.
 * When no valid token is present, the user resolver returns null.
 */
class OptionalJwtMobileMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        \Illuminate\Support\Facades\Auth::shouldUse('api');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = \Illuminate\Support\Facades\Auth::guard('api');

        // Try to authenticate if token is present
        try {
            $user = $guard->user();
        } catch (\Throwable) {
            $user = null;
        }

        // If no user or invalid user, allow as guest
        if (! $user instanceof User) {
            $request->setUserResolver(fn (): ?User => null);
            $request->attributes->set('authenticated_device', null);
            $request->attributes->set('is_guest', true);

            return $next($request);
        }

        // Validate student status - non-students with valid tokens get UNAUTHORIZED
        if (! $user->is_student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access this endpoint.',
                ],
            ], 403);
        }

        if ((int) $user->status !== UserStatus::Active->value) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => 'Your account is not active.',
                ],
            ], 403);
        }

        // Validate center match
        $resolvedCenterId = $this->resolveCenterId($request->attributes->get('resolved_center_id'));

        if ($resolvedCenterId !== null) {
            if (is_numeric($user->center_id) && (int) $user->center_id !== $resolvedCenterId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CENTER_MISMATCH',
                        'message' => 'You do not have access to this center.',
                    ],
                ], 403);
            }

            if (! $this->isActiveCenter($resolvedCenterId)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CENTER_INACTIVE',
                        'message' => 'This center is not active.',
                    ],
                ], 403);
            }
        }

        // Validate token
        $token = $guard->getToken();

        if ($token === null) {
            $request->setUserResolver(fn (): ?User => null);
            $request->attributes->set('authenticated_device', null);
            $request->attributes->set('is_guest', true);

            return $next($request);
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::query()
            ->where('access_token', (string) $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            $request->setUserResolver(fn (): ?User => null);
            $request->attributes->set('authenticated_device', null);
            $request->attributes->set('is_guest', true);

            return $next($request);
        }

        // Validate device
        $authenticatedDevice = null;

        if ($record->device_id !== null) {
            /** @var UserDevice|null $device */
            $device = UserDevice::find($record->device_id);

            if ($device === null || $device->status !== UserDevice::STATUS_ACTIVE) {
                $request->setUserResolver(fn (): ?User => null);
                $request->attributes->set('authenticated_device', null);
                $request->attributes->set('is_guest', true);

                return $next($request);
            }

            $authenticatedDevice = $device;
        }

        // Successfully authenticated
        $request->setUserResolver(fn (): User => $user);
        $request->attributes->set('authenticated_device', $authenticatedDevice);
        $request->attributes->set('is_guest', false);

        return $next($request);
    }

    private function resolveCenterId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function isActiveCenter(int $centerId): bool
    {
        return Center::query()
            ->where('id', $centerId)
            ->where('status', Center::STATUS_ACTIVE->value)
            ->exists();
    }
}
