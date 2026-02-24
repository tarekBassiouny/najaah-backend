<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\User;

interface CourseWorkflowServiceInterface
{
    public function publishCourse(Course $course, User $actor): Course;

    public function unpublishCourse(Course $course, User $actor): Course;

    /** @param array<string, mixed> $options */
    public function cloneCourse(Course $course, User $actor, array $options = []): Course;
}
