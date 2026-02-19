<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\ExtraViewRequestFilters;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExtraViewRequestQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<ExtraViewRequest>
     */
    public function build(User $admin, ExtraViewRequestFilters $filters): Builder
    {
        $query = ExtraViewRequest::query()->with(['user', 'center', 'video', 'course', 'decider']);
        $this->applyFilters($query, $filters);

        if ($this->isSystemScopedAdmin($admin)) {
            if ($filters->centerId !== null) {
                $query->where('center_id', $filters->centerId);
            }
        } else {
            $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
            $query->where('center_id', (int) $centerId);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @return Builder<ExtraViewRequest>
     */
    public function buildForCenter(User $admin, int $centerId, ExtraViewRequestFilters $filters): Builder
    {
        if (! $this->isSystemScopedAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        }

        $query = ExtraViewRequest::query()
            ->with(['user', 'center', 'video', 'course', 'decider'])
            ->where('center_id', $centerId);
        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at');
    }

    /**
     * @return LengthAwarePaginator<ExtraViewRequest>
     */
    public function paginate(User $admin, ExtraViewRequestFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<ExtraViewRequest>
     */
    public function paginateForCenter(User $admin, int $centerId, ExtraViewRequestFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<ExtraViewRequest>  $query
     */
    private function applyFilters(Builder $query, ExtraViewRequestFilters $filters): void
    {
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->search !== null) {
            $term = trim($filters->search);
            if ($term !== '') {
                $query->whereHas('user', static function (Builder $userQuery) use ($term): void {
                    $userQuery
                        ->where('name', 'like', sprintf('%%%s%%', $term))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $term))
                        ->orWhere('phone', 'like', sprintf('%%%s%%', $term));
                });
            }
        }

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->courseTitle !== null) {
            $courseTitle = trim($filters->courseTitle);
            if ($courseTitle !== '') {
                $query->whereHas('course', function (Builder $courseQuery) use ($courseTitle): void {
                    $courseQuery->whereTranslationLike(['title'], $courseTitle, $this->searchLocales());
                });
            }
        }

        if ($filters->videoId !== null) {
            $query->where('video_id', $filters->videoId);
        }

        if ($filters->videoTitle !== null) {
            $videoTitle = trim($filters->videoTitle);
            if ($videoTitle !== '') {
                $query->whereHas('video', function (Builder $videoQuery) use ($videoTitle): void {
                    $videoQuery->whereTranslationLike(['title'], $videoTitle, $this->searchLocales());
                });
            }
        }

        if ($filters->decidedBy !== null) {
            $query->where('decided_by', $filters->decidedBy);
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
