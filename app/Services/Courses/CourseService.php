<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Courses\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService implements CourseServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15, ?User $actor = null): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('id');

        if ($actor instanceof User && ! $actor->hasRole('super_admin')) {
            $centerId = $actor->center_id;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('center_id', $centerId);
        }

        return $query->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Course
    {
        if ($actor instanceof User) {
            $centerId = isset($data['center_id']) && is_numeric($data['center_id']) ? (int) $data['center_id'] : null;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
        }

        $course = Course::create($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data, ?User $actor = null): Course
    {
        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $course->update($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    public function delete(Course $course, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        $course->delete();
    }

    public function find(int $id, ?User $actor = null): ?Course
    {
        $query = Course::with(['center', 'category', 'primaryInstructor', 'instructors', 'sections.videos', 'sections.pdfs']);

        $course = $query->find($id);

        if ($actor instanceof User && $course !== null) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        return $course;
    }
}
