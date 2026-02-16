<?php

declare(strict_types=1);

namespace App\Services\AdminNotifications;

use App\Enums\AdminNotificationType;
use App\Filters\Admin\AdminNotificationFilters;
use App\Models\AdminNotification;
use App\Models\User;
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminNotificationService implements AdminNotificationServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return LengthAwarePaginator<AdminNotification>
     */
    public function list(AdminNotificationFilters $filters, User $actor): LengthAwarePaginator
    {
        $centerId = $this->getActorCenterId($actor);

        $query = AdminNotification::query()
            ->forUser($actor, $centerId)
            ->orderByDesc('created_at');

        if ($filters->unreadOnly) {
            $query->unread();
        }

        if ($filters->type !== null) {
            $query->ofType($filters->type);
        }

        if ($filters->since !== null) {
            $query->since($filters->since);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    public function getUnreadCount(User $actor): int
    {
        $centerId = $this->getActorCenterId($actor);

        return AdminNotification::query()
            ->forUser($actor, $centerId)
            ->unread()
            ->count();
    }

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
    ): AdminNotification {
        return AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'user_id' => $userId,
            'center_id' => $centerId,
        ]);
    }

    public function markAsRead(AdminNotification $notification, User $actor): AdminNotification
    {
        $this->assertCanAccess($notification, $actor);

        $notification->markAsRead();

        return $notification->fresh() ?? $notification;
    }

    public function markAllAsRead(User $actor): int
    {
        $centerId = $this->getActorCenterId($actor);

        return AdminNotification::query()
            ->forUser($actor, $centerId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function delete(AdminNotification $notification, User $actor): void
    {
        $this->assertCanAccess($notification, $actor);

        $notification->delete();
    }

    private function getActorCenterId(User $actor): ?int
    {
        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            return null;
        }

        return $actor->center_id;
    }

    private function assertCanAccess(AdminNotification $notification, User $actor): void
    {
        $centerId = $this->getActorCenterId($actor);

        $canAccess = AdminNotification::query()
            ->where('id', $notification->id)
            ->forUser($actor, $centerId)
            ->exists();

        if (! $canAccess) {
            throw new \App\Exceptions\DomainException(
                'You do not have access to this notification.',
                \App\Support\ErrorCodes::FORBIDDEN,
                403
            );
        }
    }
}
