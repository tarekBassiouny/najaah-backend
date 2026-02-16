<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class SystemSettingFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?bool $isPublic
    ) {}
}
