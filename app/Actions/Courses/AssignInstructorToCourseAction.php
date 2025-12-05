<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Instructor;
use App\Services\CourseInstructorService;

class AssignInstructorToCourseAction
{
    public function __construct(private readonly CourseInstructorService $courseInstructorService) {}

    public function execute(Course $course, Instructor $instructor, ?string $role = null): Course
    {
        $this->courseInstructorService->assign($course, $instructor, $role);

        return $course->load(['instructors', 'primaryInstructor']);
    }
}
