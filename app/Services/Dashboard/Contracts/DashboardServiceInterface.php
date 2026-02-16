<?php

declare(strict_types=1);

namespace App\Services\Dashboard\Contracts;

use App\Filters\Admin\DashboardFilters;
use App\Models\User;

interface DashboardServiceInterface
{
    /**
     * Build dashboard payload for system or center scope.
     *
     * @return array<string, mixed>
     */
    public function get(User $admin, DashboardFilters $filters): array;
}
