<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CourseServiceInterface
{
    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data): Course;

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data): Course;

    public function delete(Course $course): void;

    public function find(int $id): ?Course;
}
