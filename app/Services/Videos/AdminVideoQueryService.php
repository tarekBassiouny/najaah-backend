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
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
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
        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            return $query;
        }

        $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $query->where('center_id', $centerId);

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

        if ($filters->status !== null) {
            $query->where('encoding_status', $filters->status);
        }

        if ($filters->sourceType !== null) {
            $query->where('source_type', $filters->sourceType);
        }

        if ($filters->sourceProvider !== null) {
            $query->where('source_provider', strtolower($filters->sourceProvider));
        }

        if ($filters->createdFrom !== null) {
            $query->whereDate('created_at', '>=', $filters->createdFrom);
        }

        if ($filters->createdTo !== null) {
            $query->whereDate('created_at', '<=', $filters->createdTo);
        }

        if ($filters->query !== null) {
            $search = $filters->query;
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereTranslationLike(['title'], $search, ['en', 'ar'])
                    ->orWhereJsonContains('tags', $search);
            });
        }

        if ($filters->query === null && $filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
                $filters->search,
                ['en', 'ar']
            );
        }

        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            if ($filters->centerId !== null) {
                $centerId = $filters->centerId;
                $query->where('center_id', $centerId);
            }
        }

        return $query;
    }
}
