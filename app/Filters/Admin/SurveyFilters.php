<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class SurveyFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $scopeType,
        public readonly ?int $centerId,
        public readonly ?bool $isActive,
        public readonly ?bool $isMandatory,
        public readonly ?int $type,
        public readonly ?string $search,
        public readonly ?string $startFrom,
        public readonly ?string $startTo,
        public readonly ?string $endFrom,
        public readonly ?string $endTo
    ) {}
}
