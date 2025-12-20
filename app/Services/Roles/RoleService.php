<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
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

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return Role::create($this->normalizeRoleData($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($this->normalizeRoleData($data, $role));

        return $role->refresh() ?? $role;
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

        return $role->refresh() ?? $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeRoleData(array $data, ?Role $role = null): array
    {
        $name = (string) ($data['name'] ?? $role?->name ?? '');
        $description = $data['description'] ?? null;

        $normalized = [
            'name' => $name,
            'slug' => (string) ($data['slug'] ?? $role?->slug ?? ''),
            'name_translations' => [
                'en' => $name,
                'ar' => $name,
            ],
            'description_translations' => $description === null ? null : [
                'en' => (string) $description,
                'ar' => (string) $description,
            ],
        ];

        if ($normalized['description_translations'] === null) {
            unset($normalized['description_translations']);
        }

        return $normalized;
    }
}
