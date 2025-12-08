<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;

interface CourseWorkflowServiceInterface
{
    public function publishCourse(Course $course): Course;

    /** @param array<string, mixed> $options */
    public function cloneCourse(Course $course, array $options = []): Course;
}
