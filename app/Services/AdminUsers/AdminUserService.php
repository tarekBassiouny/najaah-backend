<?php

declare(strict_types=1);

namespace App\Services\AdminUsers;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Filters\Admin\AdminUserFilters;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return LengthAwarePaginator<User>
     */
    public function list(AdminUserFilters $filters): LengthAwarePaginator
    {
        return User::query()
            ->where('is_student', false)
            ->with(['roles.permissions'])
            ->orderByDesc('created_at')
            ->paginate(
                $filters->perPage,
                ['*'],
                'page',
                $filters->page
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $user = User::create([
            'name' => (string) $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => (string) $data['phone'],
            'password' => (string) $data['password'],
            'center_id' => $data['center_id'] ?? null,
            'is_student' => false,
            'status' => $data['status'] ?? UserStatus::Active,
        ]);

        if (isset($data['center_id']) && is_numeric($data['center_id'])) {
            $centerId = (int) $data['center_id'];
            $user->centers()->sync([$centerId => ['type' => 'admin']]);
        }

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_CREATED, [
            'center_id' => $user->center_id,
        ]);

        return $user->refresh() ?? $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?User $actor = null): User
    {
        $this->assertAdminUser($user);

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? null,
            'status' => $data['status'] ?? null,
            'center_id' => $data['center_id'] ?? null,
        ], static fn ($value): bool => $value !== null);

        $user->update($payload);

        if (array_key_exists('center_id', $data)) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $user->centers()->sync($centerId !== null ? [$centerId => ['type' => 'admin']] : []);
        }

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_UPDATED, [
            'updated_fields' => array_keys($payload),
        ]);

        return $user->refresh() ?? $user;
    }

    public function delete(User $user, ?User $actor = null): void
    {
        $this->assertAdminUser($user);
        $user->delete();

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_DELETED);
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds, ?User $actor = null): User
    {
        $this->assertAdminUser($user);
        $user->roles()->sync($roleIds);

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_ROLES_SYNCED, [
            'role_ids' => $roleIds,
        ]);

        return $user->refresh() ?? $user;
    }

    private function assertAdminUser(User $user): void
    {
        if ($user->is_student) {
            throw new DomainException('User is not an admin.', ErrorCodes::NOT_ADMIN, 422);
        }
    }
}
