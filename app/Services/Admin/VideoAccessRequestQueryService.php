<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\VideoAccessRequestFilters;
use App\Models\User;
use App\Models\VideoAccessRequest;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VideoAccessRequestQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<VideoAccessRequest>
     */
    public function buildForCenter(User $admin, int $centerId, VideoAccessRequestFilters $filters): Builder
    {
        if (! $this->isSystemScopedAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        $query = VideoAccessRequest::query()
            ->with(['user', 'video', 'course', 'center', 'decider'])
            ->where('center_id', $centerId);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at');
    }

    /**
     * @return LengthAwarePaginator<VideoAccessRequest>
     */
    public function paginateForCenter(User $admin, int $centerId, VideoAccessRequestFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<VideoAccessRequest>  $query
     */
    private function applyFilters(Builder $query, VideoAccessRequestFilters $filters): void
    {
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->videoId !== null) {
            $query->where('video_id', $filters->videoId);
        }

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->search !== null) {
            $term = trim($filters->search);

            if ($term !== '') {
                $query->where(function (Builder $wrapped) use ($term): void {
                    $wrapped->whereHas('user', static function (Builder $userQuery) use ($term): void {
                        $userQuery
                            ->where('name', 'like', sprintf('%%%s%%', $term))
                            ->orWhere('email', 'like', sprintf('%%%s%%', $term))
                            ->orWhere('phone', 'like', sprintf('%%%s%%', $term));
                    })->orWhereHas('video', function (Builder $videoQuery) use ($term): void {
                        $videoQuery->whereTranslationLike(['title'], $term, $this->searchLocales());
                    });
                });
            }
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->dateFrom)->startOfDay());
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->dateTo)->endOfDay());
        }
    }

    private function isSystemScopedAdmin(User $admin): bool
    {
        return ! $admin->is_student && ! is_numeric($admin->center_id);
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
