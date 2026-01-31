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

    /** @return LengthAwarePaginator<Enrollment> */
    public function paginateForStudent(User $student, int $perPage = 15): LengthAwarePaginator;

    /** @return LengthAwarePaginator<Enrollment> */
    public function paginateForAdmin(User $admin, EnrollmentFilters $filters): LengthAwarePaginator;

    public function assertAdminCanAccess(User $admin, Enrollment $enrollment): void;

    public function getActiveEnrollment(User $student, Course $course): ?Enrollment;
}
