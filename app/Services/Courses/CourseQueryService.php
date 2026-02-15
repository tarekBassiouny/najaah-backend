<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Filters\Admin\CourseFilters;
use App\Models\Course;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CourseQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return Builder<Course>
     */
    public function build(User $admin, CourseFilters $filters): Builder
    {
        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('created_at');

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->primaryInstructorId !== null) {
            $query->where('primary_instructor_id', $filters->primaryInstructorId);
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
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
     * @return LengthAwarePaginator<Course>
     */
    public function paginate(User $admin, CourseFilters $filters): LengthAwarePaginator
    {
        return $this->build($admin, $filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<Course>
     */
    public function paginateForCenter(User $admin, int $centerId, CourseFilters $filters): LengthAwarePaginator
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('created_at')
            ->where('center_id', $centerId);

        if ($filters->categoryId !== null) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->primaryInstructorId !== null) {
            $query->where('primary_instructor_id', $filters->primaryInstructorId);
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['title'],
                $filters->search,
                ['en', 'ar']
            );
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
