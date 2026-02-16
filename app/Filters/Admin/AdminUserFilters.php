<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class AdminUserFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId = null,
        public readonly ?int $status = null,
        public readonly ?string $search = null,
        public readonly ?int $roleId = null
    ) {}
}
