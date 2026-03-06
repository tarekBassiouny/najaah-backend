<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class BulkWhatsAppJobFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $status,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
