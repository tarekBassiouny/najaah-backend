<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class PdfFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $courseId,
        public readonly ?string $search
    ) {}
}
