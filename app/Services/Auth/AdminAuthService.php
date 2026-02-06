<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Facades\Auth;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function login(string $email, string $password): ?array
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

        $centerAccessValid = $user->hasRole('super_admin');
        if (! $centerAccessValid && is_numeric($user->center_id)) {
            $centerAccessValid = $user->isAdminOfCenter((int) $user->center_id);
        }

        if (! $user->force_password_reset && $centerAccessValid) {
            $this->auditLogService->log($user, $user, AuditActions::ADMIN_LOGIN);
        }

        return [
            'user' => $user,
            'token' => $user->force_password_reset ? null : $token,
            'requires_password_reset' => $user->force_password_reset,
            'center_access_valid' => $centerAccessValid,
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
