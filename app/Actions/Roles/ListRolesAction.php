<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Services\Roles\RoleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListRolesAction
{
    public function __construct(private readonly RoleService $service) {}

    /**
     * @return LengthAwarePaginator<\App\Models\Role>
     */
    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->service->list($perPage);
    }
}
