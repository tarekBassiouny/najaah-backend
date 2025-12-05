<?php

declare(strict_types=1);

namespace App\Actions\Courses;

use App\Models\Course;
use App\Models\Instructor;
use App\Services\CourseInstructorService;

class RemoveInstructorFromCourseAction
{
    public function __construct(private readonly CourseInstructorService $courseInstructorService) {}

    public function execute(Course $course, Instructor $instructor): Course
    {
        $this->courseInstructorService->remove($course, $instructor);

        return $course->load(['instructors', 'primaryInstructor']);
    }
}
