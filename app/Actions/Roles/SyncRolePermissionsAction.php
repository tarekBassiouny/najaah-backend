<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\Roles\RoleService;

class SyncRolePermissionsAction
{
    public function __construct(private readonly RoleService $service) {}

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function execute(Role $role, array $permissionIds): Role
    {
        return $this->service->syncPermissions($role, $permissionIds);
    }
}
