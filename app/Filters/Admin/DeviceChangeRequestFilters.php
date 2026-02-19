<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class DeviceChangeRequestFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?int $centerId,
        public readonly ?int $userId,
        public readonly ?string $search,
        public readonly ?string $requestSource,
        public readonly ?int $decidedBy,
        public readonly ?string $currentDeviceId,
        public readonly ?string $newDeviceId,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
