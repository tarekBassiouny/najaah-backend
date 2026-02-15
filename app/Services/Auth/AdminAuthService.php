<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use Illuminate\Support\Facades\Auth;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function login(string $email, string $password, ?int $resolvedCenterId = null): ?array
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'is_student' => false, // prevent student login
        ];

        $token = Auth::guard('admin')->attempt($credentials);

        if (! is_string($token) || $token === '') {
            return null;
        }

        /** @var User $user */
        $user = Auth::guard('admin')->user();

        $this->syncAdminMembership($user);

        $hasSuperAdminRole = $user->hasRole('super_admin');
        $centerAccessValid = $hasSuperAdminRole
            ? ($user->center_id === null || is_numeric($user->center_id))
            : is_numeric($user->center_id);
        $apiScopeValid = $this->centerScopeService->matchesResolvedApiCenterScope($user, $resolvedCenterId);

        if (! $apiScopeValid) {
            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = Auth::guard('admin');

            try {
                $guard->invalidate(true);
            } catch (\Throwable) {
                // Best effort only; login response still returns api_scope_valid=false.
            }
        }

        if (! $user->force_password_reset && $centerAccessValid && $apiScopeValid) {
            $this->auditLogService->log($user, $user, AuditActions::ADMIN_LOGIN);
        }

        return [
            'user' => $user,
            'token' => $user->force_password_reset ? null : $token,
            'requires_password_reset' => $user->force_password_reset,
            'center_access_valid' => $centerAccessValid,
            'api_scope_valid' => $apiScopeValid,
        ];
    }

    private function syncAdminMembership(User $user): void
    {
        if ($user->center_id === null) {
            return;
        }

        if ($user->isAdminOfCenter((int) $user->center_id)) {
            return;
        }

        $user->centers()->syncWithoutDetaching([
            (int) $user->center_id => ['type' => 'admin'],
        ]);
    }
}
