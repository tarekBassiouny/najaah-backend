<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;

class PublishCourseAction
{
    public function __construct(
        private readonly CourseWorkflowServiceInterface $courseWorkflowService
    ) {}

    public function execute(Course $course): Course
    {
        return $this->courseWorkflowService->publishCourse($course);
    }
}
