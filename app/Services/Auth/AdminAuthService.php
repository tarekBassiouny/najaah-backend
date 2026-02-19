<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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

        $centerAccessValid = $user->center_id === null || is_numeric($user->center_id);
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
            $user->last_login_at = now();
            $user->save();
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

    public function sendPasswordResetLink(string $email, bool $isInvite = false): bool
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user instanceof User || $user->is_student || $user->email === null) {
            return false;
        }

        $token = Password::broker()->createToken($user);
        $user->loadMissing('center');
        $user->notify(new AdminPasswordResetNotification($token, $isInvite));

        if ($isInvite && $user->invitation_sent_at === null) {
            $user->invitation_sent_at = now();
            $user->save();
        }

        return true;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, (string) $user->password)) {
            return false;
        }

        $user->password = $newPassword;
        $user->force_password_reset = false;
        $user->save();

        $this->auditLogService->log($user, $user, AuditActions::ADMIN_PASSWORD_CHANGED);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $updates = [];

        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }

        if (array_key_exists('phone', $data)) {
            $updates['phone'] = $data['phone'];
        }

        if (array_key_exists('country_code', $data)) {
            $updates['country_code'] = $data['country_code'];
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        $user->loadMissing(['roles.permissions', 'center']);

        return $user;
    }
}
