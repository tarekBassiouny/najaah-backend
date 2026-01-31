<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use PHPUnit\Framework\Assert;

trait EnrollmentTestHelper
{
    public function enrollStudent(User $student, Course $course, EnrollmentStatus $status = Enrollment::STATUS_ACTIVE): Enrollment
    {
        if ($course->center_id !== null && $student->center_id !== null) {
            Assert::assertSame($student->center_id, $course->center_id, 'Student center_id must match course center.');
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
