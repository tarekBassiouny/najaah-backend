<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\ErrorCodes;

class EnrollmentAccessService
{
    public function hasActiveEnrollment(User $student, Course $course, bool $lockForUpdate = false): bool
    {
        $query = Enrollment::query()
            ->activeForUserAndCourse($student, $course);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    public function assertActiveEnrollment(
        User $student,
        Course $course,
        string $message = 'Active enrollment required.',
        string $code = ErrorCodes::ENROLLMENT_REQUIRED,
        int $status = 403
    ): Enrollment {
        $enrollment = Enrollment::query()
            ->activeForUserAndCourse($student, $course)
            ->first();

        if (! $enrollment instanceof Enrollment) {
            throw new DomainException($message, $code, $status);
        }

        return $enrollment;
    }
}
