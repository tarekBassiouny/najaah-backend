<?php

declare(strict_types=1);

namespace App\Services\Enrollments\Contracts;

use App\Filters\Admin\EnrollmentFilters;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EnrollmentServiceInterface
{
    public function enroll(User $student, Course $course, string $status, ?User $actor = null): Enrollment;

    public function updateStatus(Enrollment $enrollment, string $status, ?User $actor = null): Enrollment;

    public function remove(Enrollment $enrollment, ?User $actor = null): void;

    public function sendEnrollmentNotification(Enrollment $enrollment): bool;

    /**
     * @param  array<int, int|string>  $userIds
     * @return array{
     *   approved: array<int, Enrollment>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{user_id: int|string, reason: string}>
     * }
     */
    public function bulkEnroll(User $admin, Course $course, int $centerId, array $userIds): array;

    /**
     * @param  array<int, int|string>  $enrollmentIds
     * @return array{
     *   updated: array<int, Enrollment>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{enrollment_id: int|string, reason: string}>
     * }
     */
    public function bulkUpdateStatus(User $admin, string $status, array $enrollmentIds, ?int $centerId = null): array;

    /** @return LengthAwarePaginator<Enrollment> */
    public function paginateForStudent(User $student, int $perPage = 15): LengthAwarePaginator;

    /** @return LengthAwarePaginator<Enrollment> */
    public function paginateForAdmin(User $admin, EnrollmentFilters $filters): LengthAwarePaginator;

    public function assertAdminCanAccess(User $admin, Enrollment $enrollment): void;

    public function getActiveEnrollment(User $student, Course $course): ?Enrollment;
}
