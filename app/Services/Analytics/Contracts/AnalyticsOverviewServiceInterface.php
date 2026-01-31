<?php

declare(strict_types=1);

namespace App\Services\Analytics\Contracts;

use App\Filters\Admin\AnalyticsFilters;
use App\Models\User;

interface AnalyticsOverviewServiceInterface
{
    /**
     * Generate overview analytics data.
     *
     * @return array<string, mixed>
     */
    public function handle(User $admin, AnalyticsFilters $filters): array;
}
