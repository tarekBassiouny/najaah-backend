<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\CourseStatus;
use App\Exceptions\DomainException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Access\Contracts\EnrollmentAccessServiceInterface;
use App\Support\ErrorCodes;

class EnrollmentAccessService implements EnrollmentAccessServiceInterface
{
    public function hasActiveEnrollment(User $student, Course $course, bool $lockForUpdate = false, bool $ensurePublished = true): bool
    {
        if ($ensurePublished) {
            $this->assertCoursePublished($course);
        }

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
        $this->assertCoursePublished($course);

        $enrollment = Enrollment::query()
            ->activeForUserAndCourse($student, $course)
            ->first();

        if (! $enrollment instanceof Enrollment) {
            throw new DomainException($message, $code, $status);
        }

        return $enrollment;
    }

    private function assertCoursePublished(Course $course): void
    {
        if ($course->status !== CourseStatus::Published || $course->is_published !== true) {
            throw new DomainException('Course not found.', ErrorCodes::NOT_FOUND, 404);
        }
    }
}
