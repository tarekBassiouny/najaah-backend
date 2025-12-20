<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCoursesAction
{
    public function __construct(
        private readonly CourseServiceInterface $courseService
    ) {}

    /** @return LengthAwarePaginator<Course> */
    public function execute(User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->courseService->paginate($perPage, $actor);
    }
}
