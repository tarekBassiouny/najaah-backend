<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class VideoAccessRequestFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $status,
        public readonly ?int $userId,
        public readonly ?int $videoId,
        public readonly ?int $courseId,
        public readonly ?string $search,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
