<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Actions\Concerns\NormalizesTranslations;
use App\Exceptions\DomainException;
use App\Filters\Admin\RoleFilters;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Roles\Contracts\RoleServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleService implements RoleServiceInterface
{
    use NormalizesTranslations;

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    private const TRANSLATION_FIELDS = [
        'name_translations',
        'description_translations',
    ];

    /**
     * @return LengthAwarePaginator<Role>
     */
    public function list(RoleFilters $filters, ?int $forcedCenterId = null): LengthAwarePaginator
    {
        $query = Role::query()
            ->where('is_admin_role', true)
            ->with(['permissions', 'center']);

        if ($forcedCenterId !== null) {
            $query->where('center_id', $forcedCenterId);
        } elseif ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        } else {
            $query->whereNull('center_id');
        }

        if ($filters->search !== null) {
            $term = '%'.strtolower($filters->search).'%';

            $query->where(function ($sub) use ($term): void {
                $sub->whereRaw('LOWER(slug) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$term]);
            });
        }

        return $query->orderBy('id')
            ->paginate(
                $filters->perPage,
                ['*'],
                'page',
                $filters->page
            );
    }

    public function find(int $id): ?Role
    {
        return Role::with(['permissions', 'center'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null, ?int $forcedCenterId = null): Role
    {
        $this->assertRoleManagementScope($actor, $forcedCenterId);
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS);
        $data = $this->prepareRoleData($data);
        $data['center_id'] = $forcedCenterId;

        $role = Role::create($data);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_CREATED, [
            'center_id' => $forcedCenterId,
        ]);

        return $role->fresh(['permissions', 'center']) ?? $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data, ?User $actor = null, ?int $forcedCenterId = null): Role
    {
        $this->assertRoleManagementScope($actor, $forcedCenterId);
        $this->assertRoleScope($role, $forcedCenterId);

        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS, [
            'name_translations' => $role->name_translations ?? [],
            'description_translations' => $role->description_translations ?? [],
        ]);
        $data = $this->prepareRoleData($data, $role);
        unset($data['center_id']);

        $role->update($data);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_UPDATED, [
            'updated_fields' => array_keys($data),
        ]);

        return $role->fresh(['permissions', 'center']) ?? $role;
    }

    public function delete(Role $role, ?User $actor = null, ?int $forcedCenterId = null): void
    {
        $this->assertRoleManagementScope($actor, $forcedCenterId);
        $this->assertRoleScope($role, $forcedCenterId);

        $role->delete();

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_DELETED);
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds, ?User $actor = null, ?int $forcedCenterId = null): Role
    {
        $this->assertRoleManagementScope($actor, $forcedCenterId);
        $this->assertRoleScope($role, $forcedCenterId);

        $role->permissions()->sync($permissionIds);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_PERMISSIONS_SYNCED, [
            'permission_ids' => $permissionIds,
        ]);

        return $role->fresh(['permissions', 'center']) ?? $role;
    }

    /**
     * @param  array<int, int>  $roleIds
     * @param  array<int, int>  $permissionIds
     * @return array{roles: array<int, int>, permission_ids: array<int, int>}
     */
    public function bulkSyncPermissions(array $roleIds, array $permissionIds, ?User $actor = null, ?int $forcedCenterId = null): array
    {
        $this->assertRoleManagementScope($actor, $forcedCenterId);

        $uniqueRoleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $roles = Role::query()
            ->whereIn('id', $uniqueRoleIds)
            ->when(
                $forcedCenterId === null,
                static fn ($query) => $query->whereNull('center_id'),
                static fn ($query) => $query->where('center_id', $forcedCenterId)
            )
            ->get();

        if (count($uniqueRoleIds) !== $roles->count()) {
            throw new DomainException('One or more roles were not found.', ErrorCodes::NOT_FOUND, 404);
        }

        $updatedRoles = [];

        DB::transaction(function () use ($roles, $permissionIds, $actor, &$updatedRoles): void {
            foreach ($roles as $role) {
                $role->permissions()->sync($permissionIds);
                $this->auditLogService->log($actor, $role, AuditActions::ROLE_PERMISSIONS_SYNCED, [
                    'permission_ids' => $permissionIds,
                ]);
                $updatedRoles[] = $role->id;
            }
        });

        return [
            'roles' => $updatedRoles,
            'permission_ids' => $permissionIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareRoleData(array $data, ?Role $role = null): array
    {
        $nameTranslations = $data['name_translations'] ?? $role?->name_translations ?? [];
        $name = $nameTranslations['en'] ?? $role?->name ?? '';

        $data['name'] = $name;
        $data['slug'] = $data['slug'] ?? $role?->slug ?? '';

        return $data;
    }

    private function assertSystemAdminScope(?User $actor): void
    {
        if (! $actor instanceof User || ! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            throw new DomainException('System scope access is required.', ErrorCodes::FORBIDDEN, 403);
        }
    }

    private function assertRoleManagementScope(?User $actor, ?int $forcedCenterId): void
    {
        if ($forcedCenterId === null) {
            $this->assertSystemAdminScope($actor);

            return;
        }

        if (! $actor instanceof User) {
            throw new DomainException('Authentication required.', ErrorCodes::UNAUTHORIZED, 401);
        }

        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            return;
        }

        if (! $this->centerScopeService->isCenterScopedSuperAdmin($actor)) {
            throw new DomainException('Center super admin scope is required.', ErrorCodes::FORBIDDEN, 403);
        }

        $this->centerScopeService->assertAdminCenterId($actor, $forcedCenterId);
    }

    private function assertRoleScope(Role $role, ?int $forcedCenterId): void
    {
        $roleCenterId = is_numeric($role->center_id) ? (int) $role->center_id : null;

        if ($roleCenterId !== $forcedCenterId) {
            throw new DomainException('Role not found.', ErrorCodes::NOT_FOUND, 404);
        }
    }
}
