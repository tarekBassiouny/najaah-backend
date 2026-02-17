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
    public function list(RoleFilters $filters): LengthAwarePaginator
    {
        $query = Role::query()
            ->where('is_admin_role', true)
            ->with('permissions');

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
        return Role::with('permissions')->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Role
    {
        $this->assertSystemAdminScope($actor);
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS);
        $data = $this->prepareRoleData($data);

        $role = Role::create($data);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_CREATED);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data, ?User $actor = null): Role
    {
        $this->assertSystemAdminScope($actor);
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS, [
            'name_translations' => $role->name_translations ?? [],
            'description_translations' => $role->description_translations ?? [],
        ]);
        $data = $this->prepareRoleData($data, $role);

        $role->update($data);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_UPDATED, [
            'updated_fields' => array_keys($data),
        ]);

        return $role->fresh(['permissions']) ?? $role;
    }

    public function delete(Role $role, ?User $actor = null): void
    {
        $this->assertSystemAdminScope($actor);
        $role->delete();

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_DELETED);
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds, ?User $actor = null): Role
    {
        $this->assertSystemAdminScope($actor);
        $role->permissions()->sync($permissionIds);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_PERMISSIONS_SYNCED, [
            'permission_ids' => $permissionIds,
        ]);

        return $role->fresh(['permissions']) ?? $role;
    }

    /**
     * @param array<int, int> $roleIds
     * @param array<int, int> $permissionIds
     *
     * @return array{roles: array<int, int>, permission_ids: array<int, int>}
     */
    public function bulkSyncPermissions(array $roleIds, array $permissionIds, ?User $actor = null): array
    {
        $this->assertSystemAdminScope($actor);

        $uniqueRoleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $roles = Role::query()->whereIn('id', $uniqueRoleIds)->get();

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
}
