<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\Roles\RoleService;

class CreateRoleAction
{
    public function __construct(private readonly RoleService $service) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Role
    {
        return $this->service->create($data);
    }
}
