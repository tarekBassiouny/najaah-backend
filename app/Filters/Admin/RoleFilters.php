<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class RoleFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId = null,
        public readonly ?string $search = null
    ) {}
}
