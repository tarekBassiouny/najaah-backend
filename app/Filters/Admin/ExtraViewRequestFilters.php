<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class ExtraViewRequestFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?int $centerId,
        public readonly ?int $userId,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
