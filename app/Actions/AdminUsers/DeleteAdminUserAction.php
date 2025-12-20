<?php

declare(strict_types=1);

namespace App\Actions\AdminUsers;

use App\Models\User;
use App\Services\AdminUsers\AdminUserService;

class DeleteAdminUserAction
{
    public function __construct(private readonly AdminUserService $service) {}

    public function execute(User $user): void
    {
        $this->service->delete($user);
    }
}
