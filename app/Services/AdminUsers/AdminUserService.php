<?php

declare(strict_types=1);

namespace App\Services\AdminUsers;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Filters\Admin\AdminUserFilters;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return LengthAwarePaginator<User>
     */
    public function list(AdminUserFilters $filters, ?User $actor = null): LengthAwarePaginator
    {
        $query = User::query()
            ->where('is_student', false)
            ->with(['roles.permissions', 'center'])
            ->orderByDesc('created_at');

        if ($actor instanceof User && ! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            $centerId = $this->centerScopeService->resolveAdminCenterId($actor);
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('center_id', (int) $centerId);
        } elseif ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null, ?int $forcedCenterId = null): User
    {
        $this->assertCanManageAdminUsers($actor, $forcedCenterId);
        $centerId = $forcedCenterId ?? (is_numeric($data['center_id'] ?? null) ? (int) $data['center_id'] : null);

        $user = User::create([
            'name' => (string) $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => (string) $data['phone'],
            'password' => (string) $data['password'],
            'center_id' => $centerId,
            'is_student' => false,
            'status' => $data['status'] ?? UserStatus::Active,
        ]);

        if ($centerId !== null) {
            $user->centers()->sync([$centerId => ['type' => 'admin']]);
        }

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_CREATED, [
            'center_id' => $user->center_id,
        ]);

        return ($user->refresh() ?? $user)->loadMissing(['roles.permissions', 'center']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $user,
        array $data,
        ?User $actor = null,
        ?int $forcedCenterId = null
    ): User {
        $this->assertCanManageAdminUsers($actor, $forcedCenterId);
        $this->assertAdminUser($user);
        if ($forcedCenterId !== null) {
            $this->assertAdminBelongsToCenter($user, $forcedCenterId);
        }

        $centerIdFromData = array_key_exists('center_id', $data)
            ? (is_numeric($data['center_id']) ? (int) $data['center_id'] : null)
            : null;
        $centerId = $forcedCenterId ?? $centerIdFromData;

        $payload = [];
        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $payload['email'] = $data['email'];
        }

        if (array_key_exists('phone', $data)) {
            $payload['phone'] = $data['phone'];
        }

        if (array_key_exists('password', $data)) {
            $payload['password'] = $data['password'];
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }

        if (array_key_exists('center_id', $data) || $forcedCenterId !== null) {
            $payload['center_id'] = $centerId;
        }

        $user->update($payload);

        if (array_key_exists('center_id', $data) || $forcedCenterId !== null) {
            $user->centers()->sync($centerId !== null ? [$centerId => ['type' => 'admin']] : []);
        }

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_UPDATED, [
            'updated_fields' => array_keys($payload),
        ]);

        return ($user->refresh() ?? $user)->loadMissing(['roles.permissions', 'center']);
    }

    public function delete(User $user, ?User $actor = null, ?int $forcedCenterId = null): void
    {
        $this->assertCanManageAdminUsers($actor, $forcedCenterId);
        $this->assertAdminUser($user);
        if ($forcedCenterId !== null) {
            $this->assertAdminBelongsToCenter($user, $forcedCenterId);
        }

        $user->delete();

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_DELETED);
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    public function syncRoles(
        User $user,
        array $roleIds,
        ?User $actor = null,
        ?int $forcedCenterId = null
    ): User {
        if ($forcedCenterId !== null) {
            $this->assertAdminBelongsToCenter($user, $forcedCenterId);
        }

        $this->assertCanAssignRoles($actor, $user);
        $this->assertAdminUser($user);
        $user->roles()->sync($roleIds);

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_ROLES_SYNCED, [
            'role_ids' => $roleIds,
        ]);

        return ($user->refresh() ?? $user)->loadMissing(['roles.permissions', 'center']);
    }

    private function assertAdminUser(User $user): void
    {
        if ($user->is_student) {
            throw new DomainException('User is not an admin.', ErrorCodes::NOT_ADMIN, 422);
        }
    }

    private function assertSystemAdminScope(?User $actor): void
    {
        if (! $actor instanceof User || ! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            throw new DomainException('System scope access is required.', ErrorCodes::FORBIDDEN, 403);
        }
    }

    private function assertCanManageAdminUsers(?User $actor, ?int $forcedCenterId): void
    {
        if (! $actor instanceof User) {
            throw new DomainException('Authentication required.', ErrorCodes::UNAUTHORIZED, 401);
        }

        if ($forcedCenterId === null) {
            $this->assertSystemAdminScope($actor);

            return;
        }

        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            return;
        }

        if (! $this->centerScopeService->isCenterScopedSuperAdmin($actor)) {
            throw new DomainException('System or center super admin scope is required.', ErrorCodes::FORBIDDEN, 403);
        }

        $this->centerScopeService->assertAdminCenterId($actor, $forcedCenterId);
    }

    private function assertAdminBelongsToCenter(User $user, int $centerId): void
    {
        if (! is_numeric($user->center_id) || (int) $user->center_id !== $centerId) {
            throw new DomainException('Admin user not found.', ErrorCodes::NOT_FOUND, 404);
        }
    }

    private function assertCanAssignRoles(?User $actor, User $target): void
    {
        if (! $actor instanceof User) {
            throw new DomainException('Authentication required.', ErrorCodes::UNAUTHORIZED, 401);
        }

        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            return;
        }

        if (! $this->centerScopeService->isCenterScopedSuperAdmin($actor)) {
            throw new DomainException('You do not have permission to assign roles.', ErrorCodes::FORBIDDEN, 403);
        }

        if (! is_numeric($target->center_id)) {
            throw new DomainException('Cannot assign roles outside your center scope.', ErrorCodes::CENTER_MISMATCH, 403);
        }

        $this->centerScopeService->assertAdminCenterId($actor, (int) $target->center_id);
    }
}
