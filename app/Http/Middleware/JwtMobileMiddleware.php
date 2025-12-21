<?php

namespace App\Http\Middleware;

use App\Models\Center;
use App\Models\JwtToken;
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
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        if (! ($user instanceof \App\Models\User)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        $requestedCenterId = $request->input('center_id');
        if (is_numeric($requestedCenterId)) {
            $requestedCenterId = (int) $requestedCenterId;
            if (is_numeric($user->center_id) && (int) $user->center_id !== $requestedCenterId) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CENTER_MISMATCH',
                        'message' => 'Center mismatch.',
                    ],
                ], 403);
            }

            if ($user->center_id === null) {
                $isUnbranded = Center::query()
                    ->where('id', $requestedCenterId)
                    ->where('type', 0)
                    ->exists();

                if (! $isUnbranded) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'CENTER_MISMATCH',
                            'message' => 'Center mismatch.',
                        ],
                    ], 403);
                }
            }
        }

        $token = $guard->getToken();
        if ($token === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::where('access_token', (string) $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        if ($record->device_id !== null) {
            /** @var UserDevice|null $device */
            $device = UserDevice::where('id', $record->device_id)->first();
            if ($device === null || $device->status !== UserDevice::STATUS_ACTIVE) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Authentication required.',
                    ],
                ], 403);
            }
        }

        // Set resolved user for controllers
        $request->setUserResolver(fn (): \App\Models\User => $user);

        return $next($request);
    }
}
