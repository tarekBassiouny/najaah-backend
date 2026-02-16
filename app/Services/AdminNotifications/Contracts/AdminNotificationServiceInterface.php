<?php

declare(strict_types=1);

namespace App\Services\AdminNotifications\Contracts;

use App\Enums\AdminNotificationType;
use App\Filters\Admin\AdminNotificationFilters;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminNotificationServiceInterface
{
    /**
     * @return LengthAwarePaginator<AdminNotification>
     */
    public function list(AdminNotificationFilters $filters, User $actor): LengthAwarePaginator;

    public function getUnreadCount(User $actor): int;

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function create(
        AdminNotificationType $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
        ?int $userId = null,
        ?int $centerId = null
    ): AdminNotification;

    public function markAsRead(AdminNotification $notification, User $actor): AdminNotification;

    public function markAllAsRead(User $actor): int;

    public function delete(AdminNotification $notification, User $actor): void;
}
