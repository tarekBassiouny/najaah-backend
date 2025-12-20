<?php

declare(strict_types=1);

namespace App\Services\Permissions;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    /**
     * @return Collection<int, Permission>
     */
    public function list(): Collection
    {
        return Permission::query()
            ->orderBy('id')
            ->get();
    }
}
