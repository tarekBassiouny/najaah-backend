<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class StudentFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?int $status,
        public readonly ?string $search
    ) {}
}
