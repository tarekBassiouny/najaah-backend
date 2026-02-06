<?php

declare(strict_types=1);

namespace App\Filters\Admin;

class AgentExecutionFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?int $centerId,
        public readonly ?string $agentType,
        public readonly ?int $status,
        public readonly ?int $initiatedBy
    ) {}
}
