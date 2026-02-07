<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Filters\Admin\VideoFilters;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use App\Services\Videos\Contracts\AdminVideoQueryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminVideoQueryService implements AdminVideoQueryServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return LengthAwarePaginator<Video>
     */
    /**
     * @return LengthAwarePaginator<Video>
     */
    public function paginate(User $admin, VideoFilters $filters): LengthAwarePaginator
    {
        $query = Video::query()
            ->with(['center', 'uploadSession', 'creator'])
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        $query = $this->applyFilters($query, $admin, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<Video>
     */
    public function paginateForCenter(User $admin, Center $center, VideoFilters $filters): LengthAwarePaginator
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $query = Video::query()
            ->with(['center', 'uploadSession', 'creator'])
            ->where('center_id', $center->id)
            ->orderByDesc('created_at');

        $query = $this->applyFilters($query, $admin, $filters);

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  Builder<Video>  $query
     * @return Builder<Video>
     */
    private function applyScope(Builder $query, User $admin): Builder
    {
        if ($admin->hasRole('super_admin')) {
            return $query;
        }

        $this->centerScopeService->assertAdminCenterId($admin, $admin->center_id);
        $query->where('center_id', $admin->center_id);

        return $query;
    }

    /**
     * @param  Builder<Video>  $query
     * @return Builder<Video>
     */
    private function applyFilters(Builder $query, User $admin, VideoFilters $filters): Builder
    {
        if ($filters->courseId !== null) {
            $courseId = $filters->courseId;
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
                $filters->search,
                ['en', 'ar']
            );
        }

        if ($admin->hasRole('super_admin')) {
            if ($filters->centerId !== null) {
                $centerId = $filters->centerId;
                $query->where('center_id', $centerId);
            }
        }

        return $query;
    }
}
