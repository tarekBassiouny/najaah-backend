<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class EnrollmentFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?int $courseId,
        public readonly ?int $userId,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
