<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseServiceInterface;

class ShowCourseAction
{
    public function __construct(
        private readonly CourseServiceInterface $courseService
    ) {}

    public function execute(?User $actor, int $id): ?Course
    {
        return $this->courseService->find($id, $actor);
    }
}
