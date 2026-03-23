<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional JWT authentication middleware for web guest browsing.
 *
 * Attempts to authenticate the user if a JWT token is present,
 * but allows the request to proceed without authentication for guest browsing.
 */
class OptionalJwtWebStudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('web-student');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('web-student');

        try {
            $user = $guard->user();
        } catch (\Throwable) {
            $user = null;
        }

        if (! $user instanceof User) {
            $request->setUserResolver(fn (): ?User => null);
            $request->attributes->set('authenticated_device', null);
            $request->attributes->set('is_guest', true);

            return $next($request);
        }

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
            return $this->allowAsGuest($request, $next);
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::query()
            ->where('access_token', (string) $token)
            ->where('platform', TokenPlatform::Web)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return $this->allowAsGuest($request, $next);
        }

        // Validate device
        $authenticatedDevice = null;

        if ($record->device_id !== null) {
            /** @var UserDevice|null $device */
            $device = UserDevice::find($record->device_id);

            if ($device === null || $device->status !== UserDevice::STATUS_ACTIVE) {
                return $this->allowAsGuest($request, $next);
            }

            $authenticatedDevice = $device;
        }

        $request->setUserResolver(fn (): User => $user);
        $request->attributes->set('authenticated_device', $authenticatedDevice);
        $request->attributes->set('is_guest', false);

        return $next($request);
    }

    private function allowAsGuest(Request $request, Closure $next): Response
    {
        $request->setUserResolver(fn (): ?User => null);
        $request->attributes->set('authenticated_device', null);
        $request->attributes->set('is_guest', true);

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
