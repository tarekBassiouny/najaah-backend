<?php

declare(strict_types=1);

namespace App\Actions\AdminUsers;

use App\Models\User;
use App\Services\AdminUsers\AdminUserService;

class CreateAdminUserAction
{
    public function __construct(private readonly AdminUserService $service) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        return $this->service->create($data);
    }
}
