<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\PlaybackSessionFilters;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class PlaybackSessionQueryService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return LengthAwarePaginator<PlaybackSession>
     */
    public function paginateForCenter(User $admin, int $centerId, PlaybackSessionFilters $filters): LengthAwarePaginator
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        $query = $this->buildForCenter($centerId, $filters);

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /**
     * @return Builder<PlaybackSession>
     */
    private function buildForCenter(int $centerId, PlaybackSessionFilters $filters): Builder
    {
        $query = PlaybackSession::query()
            ->with(['user', 'video', 'video.center', 'course', 'device'])
            ->whereHas('video', fn (Builder $builder) => $builder->where('center_id', $centerId));

        $this->applyFilters($query, $filters);
        $query->orderBy($filters->orderBy, $filters->orderDirection);

        return $query;
    }

    /**
     * @param  Builder<PlaybackSession>  $query
     */
    private function applyFilters(Builder $query, PlaybackSessionFilters $filters): void
    {
        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->videoId !== null) {
            $query->where('video_id', $filters->videoId);
        }

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->isFullPlay !== null) {
            $query->where('is_full_play', $filters->isFullPlay);
        }

        if ($filters->isLocked !== null) {
            $query->where('is_locked', $filters->isLocked);
        }

        if ($filters->autoClosed !== null) {
            $query->where('auto_closed', $filters->autoClosed);
        }

        if ($filters->isActive !== null) {
            $query->when($filters->isActive, fn (Builder $builder) => $builder->whereNull('ended_at'), fn (Builder $builder) => $builder->whereNotNull('ended_at'));
        }

        if ($filters->startedFrom !== null) {
            $query->where('started_at', '>=', Carbon::parse($filters->startedFrom)->startOfDay());
        }

        if ($filters->startedTo !== null) {
            $query->where('started_at', '<=', Carbon::parse($filters->startedTo)->endOfDay());
        }

        if ($filters->search !== null) {
            $term = trim($filters->search);
            if ($term !== '') {
                $like = sprintf('%%%s%%', $term);
                $query->where(function (Builder $inner) use ($like, $term): void {
                    $inner->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like))
                        ->orWhereHas('video', fn (Builder $videoQuery) => $videoQuery->whereTranslationLike(['title'], $term, $this->searchLocales()));
                });
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function searchLocales(): array
    {
        $primary = (string) app()->getLocale();
        $fallback = (string) config('app.fallback_locale');

        return array_values(array_filter(array_unique([$primary, $fallback])));
    }
}
