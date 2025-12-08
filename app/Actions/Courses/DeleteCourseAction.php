<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Services\Courses\Contracts\CourseServiceInterface;

class DeleteCourseAction
{
    public function __construct(
        private readonly CourseServiceInterface $courseService
    ) {}

    public function execute(Course $course): void
    {
        $this->courseService->delete($course);
    }
}
