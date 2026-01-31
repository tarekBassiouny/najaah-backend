<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class RoleFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage
    ) {}
}
