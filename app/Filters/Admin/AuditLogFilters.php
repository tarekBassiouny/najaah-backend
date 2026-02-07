<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class AuditLogFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?int $courseId,
        public readonly ?string $entityType,
        public readonly ?int $entityId,
        public readonly ?string $action,
        public readonly ?int $userId,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo
    ) {}
}
