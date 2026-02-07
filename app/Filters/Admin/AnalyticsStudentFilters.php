<?php

declare(strict_types=1);

namespace App\Filters\Admin;

use Illuminate\Support\Carbon;

final class AnalyticsStudentFilters extends AnalyticsFilters
{
    public function __construct(
        public readonly int $studentId,
        ?int $centerId,
        Carbon $from,
        Carbon $to,
        string $timezone
    ) {
        parent::__construct(
            centerId: $centerId,
            from: $from,
            to: $to,
            timezone: $timezone
        );
    }
}
