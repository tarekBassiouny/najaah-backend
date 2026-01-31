<?php

declare(strict_types=1);

namespace App\Services\Access\Contracts;

use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

interface EnrollmentAccessServiceInterface
{
    /**
     * Check if a student has an active enrollment for a course.
     */
    public function hasActiveEnrollment(User $student, Course $course, bool $lockForUpdate = false, bool $ensurePublished = true): bool;

    /**
     * Assert that a student has an active enrollment for a course.
     *
     * @throws DomainException
     */
    public function assertActiveEnrollment(
        User $student,
        Course $course,
        string $message = 'Active enrollment required.',
        string $code = 'ENROLLMENT_REQUIRED',
        int $status = 403
    ): Enrollment;
}
