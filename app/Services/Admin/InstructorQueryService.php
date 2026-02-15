<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Filters\Admin\InstructorFilters;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InstructorQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<Instructor>
     */
    public function build(User $admin, InstructorFilters $filters): Builder
    {
        $query = Instructor::query()
            ->with(['center', 'creator'])
            ->orderByDesc('created_at');

        if ($filters->courseId !== null) {
            $courseId = $filters->courseId;
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['name'],
                $filters->search,
                ['en', 'ar']
            );
        }

        if ($this->centerScopeService->isSystemSuperAdmin($admin)) {
            if ($filters->centerId !== null) {
                $query->where('center_id', $filters->centerId);
            }
        } else {
            $centerId = $this->centerScopeService->resolveAdminCenterId($admin);
            $this->centerScopeService->assertAdminCenterId($admin, $centerId);
            $query->where('center_id', (int) $centerId);
        }

        return $query;
    }

    /**
     * @return Builder<Instructor>
     */
    public function buildForCenter(User $admin, int $centerId, InstructorFilters $filters): Builder
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = Instructor::query()
            ->with(['center', 'creator'])
            ->where('center_id', $centerId)
            ->orderByDesc('created_at');

        if ($filters->courseId !== null) {
            $courseId = $filters->courseId;
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['name'],
                $filters->search,
                ['en', 'ar']
            );
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function paginate(User $admin, InstructorFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function paginateForCenter(User $admin, int $centerId, InstructorFilters $filters): LengthAwarePaginator
    {
        return $this->buildForCenter($admin, $centerId, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
