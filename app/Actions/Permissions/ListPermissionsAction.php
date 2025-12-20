<?php

declare(strict_types=1);

namespace App\Actions\Permissions;

use App\Models\Permission;
use App\Services\Permissions\PermissionService;
use Illuminate\Database\Eloquent\Collection;

class ListPermissionsAction
{
    public function __construct(private readonly PermissionService $service) {}

    /**
     * @return Collection<int, Permission>
     */
    public function execute(): Collection
    {
        return $this->service->list();
    }
}
