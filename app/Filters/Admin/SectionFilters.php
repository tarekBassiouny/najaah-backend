<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class SectionFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?bool $isPublished
    ) {}
}
