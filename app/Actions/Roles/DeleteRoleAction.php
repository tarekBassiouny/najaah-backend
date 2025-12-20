<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\Roles\RoleService;

class DeleteRoleAction
{
    public function __construct(private readonly RoleService $service) {}

    public function execute(Role $role): void
    {
        $this->service->delete($role);
    }
}
