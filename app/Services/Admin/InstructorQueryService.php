<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Instructor;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Database\Eloquent\Builder;

class InstructorQueryService
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Instructor>
     */
    public function build(User $admin, array $filters): Builder
    {
        $query = Instructor::query()
            ->with(['center', 'creator'])
            ->orderByDesc('created_at');

        if (isset($filters['course_id']) && is_numeric($filters['course_id'])) {
            $courseId = (int) $filters['course_id'];
            $query->whereHas('courses', static function (Builder $builder) use ($courseId): void {
                $builder->where('courses.id', $courseId);
            });
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('name_translations', 'like', '%'.$term.'%');
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
