<?php

declare(strict_types=1);

namespace App\Filters\Mobile;

class CourseFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $categoryId,
        public readonly ?int $instructorId,
        public readonly ?bool $enrolled,
        public readonly ?string $publishFrom,
        public readonly ?string $publishTo
    ) {}
}
