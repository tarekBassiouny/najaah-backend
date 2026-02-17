<?php

declare(strict_types=1);

namespace App\Services\Roles\Contracts;

use App\Filters\Admin\RoleFilters;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoleServiceInterface
{
    /** @return LengthAwarePaginator<Role> */
    public function list(RoleFilters $filters): LengthAwarePaginator;

    public function find(int $id): ?Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data, ?User $actor = null): Role;

    public function delete(Role $role, ?User $actor = null): void;

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds, ?User $actor = null): Role;

    /**
     * @param array<int, int> $roleIds
     * @param array<int, int> $permissionIds
     *
     * @return array{roles: array<int, int>, permission_ids: array<int, int>}
     */
    public function bulkSyncPermissions(array $roleIds, array $permissionIds, ?User $actor = null): array;
}
