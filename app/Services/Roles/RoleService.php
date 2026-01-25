<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Role;
use App\Services\Roles\Contracts\RoleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService implements RoleServiceInterface
{
    use NormalizesTranslations;

    private const TRANSLATION_FIELDS = [
        'name_translations',
        'description_translations',
    ];

    /**
     * @return LengthAwarePaginator<Role>
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS);
        $data = $this->prepareRoleData($data);

        return Role::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS, [
            'name_translations' => $role->name_translations ?? [],
            'description_translations' => $role->description_translations ?? [],
        ]);
        $data = $this->prepareRoleData($data, $role);

        $role->update($data);

        return $role->fresh(['permissions']) ?? $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);

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
