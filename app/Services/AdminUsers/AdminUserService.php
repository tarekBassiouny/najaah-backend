<?php

declare(strict_types=1);

namespace App\Services\AdminUsers;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Filters\Admin\AdminUserFilters;
use App\Models\User;
use App\Services\AdminUsers\Contracts\AdminUserServiceInterface;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AdminUserService implements AdminUserServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CenterScopeService $centerScopeService,
        private readonly AdminAuthServiceInterface $adminAuthService
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

        if ($filters->search !== null) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('email', 'like', '%'.$filters->search.'%')
                    ->orWhere('phone', 'like', '%'.$filters->search.'%');
            });
        }

        if ($filters->roleId !== null) {
            $query->whereHas('roles', function (Builder $builder) use ($filters): void {
                $builder->where('roles.id', $filters->roleId);
            });
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
            'password' => Str::random(32),
            'center_id' => $centerId,
            'is_student' => false,
            'status' => $data['status'] ?? UserStatus::Active,
            'force_password_reset' => true,
        ]);

        if ($centerId !== null) {
            $user->centers()->sync([$centerId => ['type' => 'admin']]);
        }

        $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_CREATED, [
            'center_id' => $user->center_id,
            'invite_only' => true,
        ]);

        if ($user->email !== null) {
            $this->adminAuthService->sendPasswordResetLink($user->email, true);
        }

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

    public function updateStatus(User $user, int $status, ?User $actor = null, ?int $forcedCenterId = null): User
    {
        return $this->update($user, ['status' => $status], $actor, $forcedCenterId);
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, reason: string}>
     * }
     */
    public function bulkUpdateStatus(array $userIds, int $status, ?User $actor = null, ?int $forcedCenterId = null): array
    {
        $this->assertCanManageAdminUsers($actor, $forcedCenterId);
        $uniqueUserIds = array_values(array_unique(array_map('intval', $userIds)));
        $users = User::query()
            ->whereIn('id', $uniqueUserIds)
            ->get()
            ->keyBy('id');

        $results = [
            'updated' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueUserIds as $userId) {
            $user = $users->get($userId);

            if (! $user instanceof User) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => 'Admin user not found.',
                ];

                continue;
            }

            if ($user->is_student) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => 'User is not an admin.',
                ];

                continue;
            }

            if ((int) $user->status === $status) {
                $results['skipped'][] = [
                    'user_id' => $userId,
                    'reason' => 'Admin already has the requested status.',
                ];

                continue;
            }

            try {
                $results['updated'][] = $this->updateStatus($user, $status, $actor, $forcedCenterId);
            } catch (DomainException $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function assignCenter(User $user, int $centerId, ?User $actor = null): User
    {
        return $this->update($user, ['center_id' => $centerId], $actor);
    }

    /**
     * @param  array<int, array{user_id:int, center_id:int}>  $assignments
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, center_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, center_id: int, reason: string}>
     * }
     */
    public function bulkAssignCenters(array $assignments, ?User $actor = null): array
    {
        $this->assertCanManageAdminUsers($actor, null);

        $userIds = array_values(array_unique(array_map(
            static fn (array $assignment): int => $assignment['user_id'],
            $assignments
        )));

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $results = [
            'updated' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($assignments as $assignment) {
            $userId = (int) $assignment['user_id'];
            $centerId = (int) $assignment['center_id'];
            $user = $users->get($userId);

            if (! $user instanceof User) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'center_id' => $centerId,
                    'reason' => 'Admin user not found.',
                ];

                continue;
            }

            if ($user->is_student) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'center_id' => $centerId,
                    'reason' => 'User is not an admin.',
                ];

                continue;
            }

            if (is_numeric($user->center_id) && (int) $user->center_id === $centerId) {
                $results['skipped'][] = [
                    'user_id' => $userId,
                    'center_id' => $centerId,
                    'reason' => 'Admin is already assigned to this center.',
                ];

                continue;
            }

            try {
                $results['updated'][] = $this->assignCenter($user, $centerId, $actor);
            } catch (DomainException $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'center_id' => $centerId,
                    'reason' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'center_id' => $centerId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $results;
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

    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $roleIds
     * @return array{
     *   updated: array<int, User>,
     *   skipped: array<int, array{user_id: int, reason: string}>,
     *   failed: array<int, array{user_id: int, reason: string}>
     * }
     */
    public function bulkSyncRoles(array $userIds, array $roleIds, ?User $actor = null, ?int $forcedCenterId = null): array
    {
        $uniqueUserIds = array_values(array_unique(array_map('intval', $userIds)));
        $normalizedRoleIds = array_values(array_unique(array_map('intval', $roleIds)));
        sort($normalizedRoleIds);

        $users = User::query()
            ->whereIn('id', $uniqueUserIds)
            ->with('roles')
            ->get()
            ->keyBy('id');

        $results = [
            'updated' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueUserIds as $userId) {
            $user = $users->get($userId);

            if (! $user instanceof User) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => 'Admin user not found.',
                ];

                continue;
            }

            try {
                if ($forcedCenterId !== null) {
                    $this->assertAdminBelongsToCenter($user, $forcedCenterId);
                }

                $this->assertCanAssignRoles($actor, $user);
                $this->assertAdminUser($user);

                $currentRoleIds = $user->roles
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                if ($currentRoleIds === $normalizedRoleIds) {
                    $results['skipped'][] = [
                        'user_id' => $userId,
                        'reason' => 'Admin already has the requested roles.',
                    ];

                    continue;
                }

                $user->roles()->sync($normalizedRoleIds);
                $this->auditLogService->log($actor, $user, AuditActions::ADMIN_USER_ROLES_SYNCED, [
                    'role_ids' => $normalizedRoleIds,
                    'bulk' => true,
                ]);

                $results['updated'][] = ($user->refresh() ?? $user)->loadMissing(['roles.permissions', 'center']);
            } catch (DomainException $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $results;
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
