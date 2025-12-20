<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminVideoQueryService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /**
     * @return LengthAwarePaginator<Video>
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Video>
     */
    public function paginate(User $admin, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Video::query()
            ->with(['uploadSession', 'creator'])
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        $query = $this->applyFilters($query, $admin, $filters);

        return $query->paginate($perPage);
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
        $query->whereHas('creator', static function (Builder $builder) use ($admin): void {
            $builder->where('center_id', $admin->center_id);
        });

        return $query;
    }

    /**
     * @param  Builder<Video>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Video>
     */
    private function applyFilters(Builder $query, User $admin, array $filters): Builder
    {
        if (isset($filters['course_id']) && is_numeric($filters['course_id'])) {
            $courseId = (int) $filters['course_id'];
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('title_translations', 'like', '%'.$term.'%');
            }
        }

        if ($admin->hasRole('super_admin')) {
            if (isset($filters['center_id']) && is_numeric($filters['center_id'])) {
                $centerId = (int) $filters['center_id'];
                $query->whereHas('creator', static function (Builder $builder) use ($centerId): void {
                    $builder->where('center_id', $centerId);
                });
            }
        }

        return $query;
    }
}
