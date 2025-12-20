<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CourseServiceInterface
{
    /** @return LengthAwarePaginator<Course> */
    public function paginate(int $perPage = 15, ?User $actor = null): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Course;

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data, ?User $actor = null): Course;

    public function delete(Course $course, ?User $actor = null): void;

    public function find(int $id, ?User $actor = null): ?Course;
}
