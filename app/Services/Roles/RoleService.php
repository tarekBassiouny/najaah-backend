<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Actions\Concerns\NormalizesTranslations;
use App\Filters\Admin\RoleFilters;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Roles\Contracts\RoleServiceInterface;
use App\Support\AuditActions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService implements RoleServiceInterface
{
    use NormalizesTranslations;

    public function __construct(private readonly AuditLogService $auditLogService) {}

    private const TRANSLATION_FIELDS = [
        'name_translations',
        'description_translations',
    ];

    /**
     * @return LengthAwarePaginator<Role>
     */
    public function list(RoleFilters $filters): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('id')
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
        $role->delete();

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_DELETED);
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds, ?User $actor = null): Role
    {
        $role->permissions()->sync($permissionIds);

        $this->auditLogService->log($actor, $role, AuditActions::ROLE_PERMISSIONS_SYNCED, [
            'permission_ids' => $permissionIds,
        ]);

        return $role->fresh(['permissions']) ?? $role;
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
}
