<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class DashboardFilters
{
    public function __construct(
        public readonly ?int $centerId
    ) {}
}
