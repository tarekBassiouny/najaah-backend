<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\Roles\RoleService;

class UpdateRoleAction
{
    public function __construct(private readonly RoleService $service) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Role $role, array $data): Role
    {
        return $this->service->update($role, $data);
    }
}
