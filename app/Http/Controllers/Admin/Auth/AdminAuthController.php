<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminChangePasswordRequest;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Http\Requests\Admin\Auth\AdminPasswordForgotRequest;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Support\AuditActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthServiceInterface $authService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Authenticate an admin.
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        /** @var array{email:string,password:string} $data */
        $data = $request->validated();
        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        $result = $this->authService->login(
            $data['email'],
            $data['password'],
            is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null
        );

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

        if (! $result['api_scope_valid']) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Center mismatch.',
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

    /**
     * Log out the current admin.
     */
    public function logout(): JsonResponse
    {
        try {
            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('admin');
            $user = $guard->user();
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

            if ($user instanceof User) {
                $this->auditLogService->log($user, $user, AuditActions::ADMIN_LOGOUT);
            }

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

    /**
     * Get the current admin profile.
     */
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

        $user->loadMissing(['roles.permissions', 'center']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new AdminUserResource($user),
            ],
        ]);
    }

    /**
     * Request a password reset link (used for both forgot-password and invite flows).
     */
    public function forgotPassword(AdminPasswordForgotRequest $request): JsonResponse
    {
        /** @var array{email:string} $data */
        $data = $request->validated();
        $this->authService->sendPasswordResetLink($data['email']);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'If the account exists, a password reset link has been sent.',
            ],
        ]);
    }

    /**
     * Change the current admin password.
     */
    public function changePassword(AdminChangePasswordRequest $request): JsonResponse
    {
        /** @var array{current_password:string,new_password:string} $data */
        $data = $request->validated();
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

        $changed = $this->authService->changePassword($user, $data['current_password'], $data['new_password']);
        if (! $changed) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Current password is incorrect.',
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Password changed successfully.',
            ],
        ]);
    }

    /**
     * Refresh the admin access token.
     */
    public function refresh(): JsonResponse
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

            $newToken = $guard->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $newToken,
                ],
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REFRESH_FAILED',
                    'message' => 'Failed to refresh token.',
                ],
            ], 401);
        }
    }
}
