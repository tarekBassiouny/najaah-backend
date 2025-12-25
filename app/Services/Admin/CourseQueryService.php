<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Database\Eloquent\Builder;

class CourseQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Course>
     */
    public function build(User $admin, array $filters): Builder
    {
        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('created_at');

        if (isset($filters['category_id']) && is_numeric($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['primary_instructor_id']) && is_numeric($filters['primary_instructor_id'])) {
            $query->where('primary_instructor_id', (int) $filters['primary_instructor_id']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('title_translations', 'like', '%'.$term.'%');
            }
        }

        if ($admin->hasRole('super_admin')) {
            if (isset($filters['center_id']) && is_numeric($filters['center_id'])) {
                $query->where('center_id', (int) $filters['center_id']);
            }
        } else {
            $centerId = $admin->center_id;
            $this->centerScopeService->assertAdminCenterId($admin, is_numeric($centerId) ? (int) $centerId : null);
            $query->where('center_id', (int) $centerId);
        }

        return $query;
    }
}
