<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\User;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthServiceInterface $authService) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        /** @var array{email:string,password:string} $data */
        $data = $request->validated();
        $result = $this->authService->login($data['email'], $data['password']);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid credentials.',
                ],
            ], 401);
        }

        if ($result['requires_password_reset']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_RESET_REQUIRED',
                    'message' => 'Password reset is required before login.',
                ],
            ], 403);
        }

        if (! $result['center_access_valid']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_ACCESS_REQUIRED',
                    'message' => 'Admin center access is required before login.',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new AdminUserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        try {
            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('admin');
            $token = $guard->getToken();
            if (! $token) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_MISSING',
                        'message' => 'Token not provided.',
                    ],
                ], 400);
            }

            $guard->invalidate(true);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Logged out',
                ],
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LOGOUT_FAILED',
                    'message' => 'Failed to logout.',
                ],
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('admin');
        $user = $guard->user() ?? $guard->authenticate();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthorized.',
                ],
            ], 401);
        }

        $user->loadMissing('roles.permissions');

        $userData = (new AdminUserResource($user))->toArray(request());
        $userData['roles'] = $user->roles
            ->map(static function ($role): array {
                return [
                    'slug' => $role->slug,
                    'permissions' => $role->permissions->pluck('name')->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $userData,
            ],
        ]);
    }

    public function refresh(): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('admin');
        $newToken = $guard->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $newToken,
            ],
        ]);
    }
}
