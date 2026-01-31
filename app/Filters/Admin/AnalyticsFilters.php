<?php

declare(strict_types=1);

namespace App\Filters\Admin;

use Illuminate\Support\Carbon;

class AnalyticsFilters
{
    public function __construct(
        public readonly ?int $centerId,
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $timezone
    ) {}
}
