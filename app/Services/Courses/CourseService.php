<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService implements CourseServiceInterface
{
    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Course::query()
            ->with(['center', 'category', 'primaryInstructor', 'instructors'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Course
    {
        $course = Course::create($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data): Course
    {
        $course->update($data);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }

    public function find(int $id): ?Course
    {
        return Course::with(['center', 'category', 'primaryInstructor', 'instructors', 'sections.videos', 'sections.pdfs'])->find($id);
    }
}
