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
        public readonly ?string $query,
        public readonly ?int $status,
        public readonly ?int $sourceType,
        public readonly ?string $sourceProvider,
        public readonly ?string $createdFrom,
        public readonly ?string $createdTo
    ) {}
}
