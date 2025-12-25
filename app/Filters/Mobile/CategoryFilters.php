<?php

declare(strict_types=1);

namespace App\Filters\Mobile;

class CategoryFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search
    ) {}
}
