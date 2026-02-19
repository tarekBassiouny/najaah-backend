<?php

declare(strict_types=1);

namespace App\Services\AdminNotifications;

use App\Enums\AdminNotificationType;
use App\Filters\Admin\AdminNotificationFilters;
use App\Models\AdminNotification;
use App\Models\AdminNotificationUserState;
use App\Models\User;
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        /** @var Builder<AdminNotification> $query */
        $query = AdminNotification::query()
            ->orderByDesc('created_at');
        $this->applyVisibilityScope($query, $actor);

        $this->excludeDeletedForActor($query, $actor);

        $query->with([
            'userStates' => function (Relation $builder) use ($actor): void {
                $builder->where('user_id', $actor->id)
                    ->whereNull('deleted_at');
            },
        ]);

        if ($filters->unreadOnly) {
            $query->whereDoesntHave('userStates', function (Builder $state) use ($actor): void {
                $state->where('user_id', $actor->id)
                    ->whereNotNull('read_at');
            });
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
        $query = AdminNotification::query();
        $this->applyVisibilityScope($query, $actor);

        $this->excludeDeletedForActor($query, $actor);

        return $query->whereDoesntHave('userStates', function (Builder $state) use ($actor): void {
            $state->where('user_id', $actor->id)
                ->whereNotNull('read_at');
        })->count();
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

        $this->upsertUserState($notification->id, $actor->id, now());

        return $notification->fresh() ?? $notification;
    }

    public function markAllAsRead(User $actor): int
    {
        $query = AdminNotification::query();
        $this->applyVisibilityScope($query, $actor);

        $this->excludeDeletedForActor($query, $actor);

        $unreadIds = $query
            ->whereDoesntHave('userStates', function (Builder $state) use ($actor): void {
                $state->where('user_id', $actor->id)
                    ->whereNotNull('read_at');
            })
            ->pluck('id')
            ->all();

        foreach ($unreadIds as $notificationId) {
            $this->upsertUserState($notificationId, $actor->id, now());
        }

        return count($unreadIds);
    }

    public function delete(AdminNotification $notification, User $actor): void
    {
        $this->assertCanAccess($notification, $actor);

        $state = AdminNotificationUserState::updateOrCreate(
            [
                'admin_notification_id' => $notification->id,
                'user_id' => $actor->id,
            ],
            [
                'read_at' => null,
            ]
        );

        if (! $state->trashed()) {
            $state->delete();
        }
    }

    private function assertCanAccess(AdminNotification $notification, User $actor): void
    {
        $query = AdminNotification::query()->where('id', $notification->id);
        $this->applyVisibilityScope($query, $actor);

        $this->excludeDeletedForActor($query, $actor);

        $canAccess = $query->exists();

        if (! $canAccess) {
            throw new \App\Exceptions\DomainException(
                'You do not have access to this notification.',
                \App\Support\ErrorCodes::FORBIDDEN,
                403
            );
        }
    }

    /**
     * @param  Builder<AdminNotification>  $query
     */
    private function excludeDeletedForActor(Builder $query, User $actor): void
    {
        $query->whereDoesntHave('userStates', function (Builder $state) use ($actor): void {
            $state->where('user_id', $actor->id)
                ->whereNotNull('deleted_at');
        });
    }

    /**
     * @param  Builder<AdminNotification>  $query
     */
    private function applyVisibilityScope(Builder $query, User $actor): void
    {
        if (! $actor->is_student && ! is_numeric($actor->center_id)) {
            $query->where(function (Builder $visible) use ($actor): void {
                $visible->whereNull('user_id')
                    ->orWhere('user_id', $actor->id);
            });

            return;
        }

        $actorCenterId = $this->centerScopeService->resolveAdminCenterId($actor);
        $query->where(function (Builder $visible) use ($actor, $actorCenterId): void {
            $visible->where('user_id', $actor->id);

            if ($actorCenterId !== null) {
                $visible->orWhere(function (Builder $centerScoped) use ($actorCenterId): void {
                    $centerScoped->whereNull('user_id')
                        ->where('center_id', $actorCenterId);
                });
            }
        });
    }

    private function upsertUserState(int $notificationId, int $userId, ?\DateTimeInterface $readAt = null): void
    {
        AdminNotificationUserState::updateOrCreate(
            [
                'admin_notification_id' => $notificationId,
                'user_id' => $userId,
            ],
            [
                'read_at' => $readAt,
                'deleted_at' => null,
            ]
        );
    }
}
