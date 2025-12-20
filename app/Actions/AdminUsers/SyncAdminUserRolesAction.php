<?php

declare(strict_types=1);

namespace App\Actions\AdminUsers;

use App\Models\User;
use App\Services\AdminUsers\AdminUserService;

class SyncAdminUserRolesAction
{
    public function __construct(private readonly AdminUserService $service) {}

    /**
     * @param  array<int, int>  $roleIds
     */
    public function execute(User $user, array $roleIds): User
    {
        return $this->service->syncRoles($user, $roleIds);
    }
}
