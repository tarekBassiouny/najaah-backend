<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class CourseFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?int $categoryId,
        public readonly ?int $primaryInstructorId,
        public readonly ?string $search
    ) {}
}
