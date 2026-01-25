<?php

declare(strict_types=1);

namespace App\Services\Roles\Contracts;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleServiceInterface
{
    /**
     * @return LengthAwarePaginator<Role>
     */
    public function list(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role;

    public function delete(Role $role): void;

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): Role;
}
