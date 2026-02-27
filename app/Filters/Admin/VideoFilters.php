<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class VideoFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?int $courseId,
        public readonly ?string $search,
        public readonly ?string $query = null,
        public readonly ?int $status = null,
        public readonly ?int $sourceType = null,
        public readonly ?string $sourceProvider = null,
        public readonly ?string $createdFrom = null,
        public readonly ?string $createdTo = null
    ) {}
}
