<?php

declare(strict_types=1);

namespace App\Filters\Admin;

use App\Enums\AdminNotificationType;

class AdminNotificationFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly bool $unreadOnly = false,
        public readonly ?AdminNotificationType $type = null,
        public readonly ?int $since = null,
    ) {}
}
