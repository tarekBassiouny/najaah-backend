<?php

declare(strict_types=1);

namespace App\Services\Analytics\Contracts;

use App\Filters\Admin\AnalyticsStudentFilters;
use App\Models\User;

interface AnalyticsStudentEngagementServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $admin, User $student, AnalyticsStudentFilters $filters): array;
}
