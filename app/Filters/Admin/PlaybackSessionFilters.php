<?php

declare(strict_types=1);

namespace App\Filters\Admin;

final class PlaybackSessionFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $userId,
        public readonly ?int $videoId,
        public readonly ?int $courseId,
        public readonly ?bool $isFullPlay,
        public readonly ?bool $isLocked,
        public readonly ?bool $autoClosed,
        public readonly ?bool $isActive,
        public readonly ?string $search,
        public readonly ?string $startedFrom,
        public readonly ?string $startedTo,
        public readonly string $orderBy,
        public readonly string $orderDirection,
    ) {}
}
