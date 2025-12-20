<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;

class CloneCourseAction
{
    public function __construct(
        private readonly CourseWorkflowServiceInterface $courseWorkflowService
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(User $actor, Course $course, array $options = []): Course
    {
        return $this->courseWorkflowService->cloneCourse($course, $actor, $options);
    }
}
