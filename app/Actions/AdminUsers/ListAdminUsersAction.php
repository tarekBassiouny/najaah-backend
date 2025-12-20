<?php

declare(strict_types=1);

namespace App\Actions\AdminUsers;

use App\Services\AdminUsers\AdminUserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminUsersAction
{
    public function __construct(private readonly AdminUserService $service) {}

    /**
     * @return LengthAwarePaginator<\App\Models\User>
     */
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->service->list($perPage);
    }
}
