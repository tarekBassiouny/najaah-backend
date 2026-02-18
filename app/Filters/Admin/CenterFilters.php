<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class CenterFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $slug,
        public readonly ?int $type,
        public readonly ?int $tier,
        public readonly ?bool $isFeatured,
        public readonly ?int $status,
        public readonly ?bool $isDemo,
        public readonly ?string $onboardingStatus,
        public readonly ?string $search,
        public readonly ?string $createdFrom,
        public readonly ?string $createdTo,
        public readonly ?string $updatedFrom,
        public readonly ?string $updatedTo,
        public readonly ?string $deleted
    ) {}
}
