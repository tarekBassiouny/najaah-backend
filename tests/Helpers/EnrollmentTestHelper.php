<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

trait EnrollmentTestHelper
{
    public function enrollStudent(User $student, Course $course, int $status = Enrollment::STATUS_ACTIVE): Enrollment
    {
        /** @var Enrollment $enrollment */
        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'center_id' => $course->center_id,
            'status' => $status,
        ]);

        return $enrollment;
    }
}
