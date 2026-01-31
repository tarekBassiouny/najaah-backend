<?php

declare(strict_types=1);

namespace App\Services\Courses\Contracts;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use App\Models\User;

interface CourseInstructorServiceInterface
{
    public function assign(Course $course, Instructor $instructor, ?string $role = null, ?User $actor = null): CourseInstructor;

    public function remove(Course $course, Instructor $instructor, ?User $actor = null): void;
}
