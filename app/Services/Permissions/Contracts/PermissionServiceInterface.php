<?php

declare(strict_types=1);

namespace App\Services\Permissions\Contracts;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

interface PermissionServiceInterface
{
    /**
     * @return Collection<int, Permission>
     */
    public function list(): Collection;
}
