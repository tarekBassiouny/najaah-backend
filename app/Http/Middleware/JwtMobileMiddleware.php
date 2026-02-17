<?php

namespace App\Http\Middleware;

use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtMobileMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        Auth::shouldUse('api');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        try {
            $user = $guard->user() ?? $guard->authenticate();
        } catch (\Throwable) {
            return $this->deny();
        }

        if (! $user instanceof User) {
            return $this->deny();
        }

        if (! $user->is_student) {
            return $this->deny('UNAUTHORIZED', 'Only students can access this endpoint.');
        }

        /*
        |--------------------------------------------------------------------------
        | Center validation
        |--------------------------------------------------------------------------
        */
        $resolvedCenterId = $this->resolveCenterId($request->attributes->get('resolved_center_id'));

        if ($resolvedCenterId === null) {
            if (is_numeric($user->center_id)) {
                return $this->deny('CENTER_MISMATCH', 'Center mismatch.');
            }
        } elseif (! is_numeric($user->center_id) || (int) $user->center_id !== $resolvedCenterId) {
            return $this->deny('CENTER_MISMATCH', 'Center mismatch.');
        }

        $requestedCenterId = $request->input('center_id');

        if (is_numeric($requestedCenterId)) {
            $requestedCenterId = (int) $requestedCenterId;
            if ($resolvedCenterId !== null && $requestedCenterId !== $resolvedCenterId) {
                return $this->deny('CENTER_MISMATCH', 'Center mismatch.');
            }

            if (! $this->studentCanAccessRequestedCenter($user, $requestedCenterId)) {
                return $this->deny('CENTER_MISMATCH', 'Center mismatch.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Token validation
        |--------------------------------------------------------------------------
        */
        $token = $guard->getToken();

        if ($token === null) {
            return $this->deny();
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::query()
            ->where('access_token', (string) $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return $this->deny();
        }

        /*
        |--------------------------------------------------------------------------
        | Device validation
        |--------------------------------------------------------------------------
        */
        $authenticatedDevice = null;

        if ($record->device_id !== null) {
            /** @var UserDevice|null $device */
            $device = UserDevice::find($record->device_id);

            if ($device === null || $device->status !== UserDevice::STATUS_ACTIVE) {
                return $this->deny('DEVICE_MISMATCH', 'Device is not authorized for this user.');
            }

            $authenticatedDevice = $device;
        }

        // Bind resolved user to request
        $request->setUserResolver(fn (): User => $user);

        // Store authenticated device in request for downstream use
        $request->attributes->set('authenticated_device', $authenticatedDevice);

        return $next($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Deny helper
    |--------------------------------------------------------------------------
    */
    private function deny(string $code = 'UNAUTHORIZED', string $message = 'Authentication required.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    private function resolveCenterId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function studentCanAccessRequestedCenter(User $user, int $requestedCenterId): bool
    {
        if (is_numeric($user->center_id)) {
            return (int) $user->center_id === $requestedCenterId;
        }

        return Center::query()
            ->where('id', $requestedCenterId)
            ->where('type', 0)
            ->exists();
    }
}
