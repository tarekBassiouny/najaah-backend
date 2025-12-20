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
        if ($course->center_id !== null) {
            $student->centers()->syncWithoutDetaching([
                $course->center_id => ['type' => 'student'],
            ]);

            if ($student->center_id === null) {
                $student->center_id = $course->center_id;
                $student->save();
            }
        }

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
