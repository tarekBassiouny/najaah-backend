<?php

declare(strict_types=1);

namespace App\Services\Enrollments;

use App\Enums\CenterType;
use App\Enums\EnrollmentStatus;
use App\Filters\Admin\EnrollmentFilters;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Access\StudentAccessService;
use App\Services\AdminNotifications\AdminNotificationDispatcher;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Students\Contracts\StudentNotificationServiceInterface;
use App\Support\AuditActions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentNotificationServiceInterface $notificationService,
        private readonly StudentAccessService $studentAccessService,
        private readonly AuditLogService $auditLogService,
        private readonly AdminNotificationDispatcher $adminNotificationDispatcher
    ) {}

    public function enroll(User $student, Course $course, string $status, ?User $actor = null): Enrollment
    {
        $this->assertEnrollmentEligibility($student, $course, $actor);
        $statusValue = $this->normalizeStatus($status);

        $result = DB::transaction(function () use ($student, $course, $statusValue, $actor): Enrollment {
            $existing = Enrollment::withTrashed()
                ->where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existing !== null && ! $existing->trashed()) {
                throw ValidationException::withMessages([
                    'enrollment' => ['Student is already enrolled in this course.'],
                ]);
            }

            if ($existing !== null && $existing->trashed()) {
                $existing->restore();
                $enrollment = $existing;
            } else {
                $enrollment = new Enrollment([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'center_id' => $course->center_id,
                    'enrolled_at' => Carbon::now(),
                ]);
            }

            $enrollment->status = $statusValue;
            $enrollment->center_id = $course->center_id;
            $enrollment->enrolled_at = $enrollment->enrolled_at ?? Carbon::now();
            $enrollment->save();

            $this->log(AuditActions::ENROLLMENT_CREATED, $actor, $course, $enrollment);

            return $enrollment->fresh(['course', 'user']) ?? $enrollment;
        });

        // Send enrollment notification (non-blocking)
        $this->notificationService->sendEnrollmentNotification($result);

        // Notify admin of new enrollment
        $this->adminNotificationDispatcher->dispatchNewEnrollment($result);

        return $result;
    }

    /**
     * @param  array<int, int|string>  $userIds
     * @return array{
     *   approved: array<int, Enrollment>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{user_id: int|string, reason: string}>
     * }
     */
    public function bulkEnroll(User $admin, Course $course, int $centerId, array $userIds): array
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        if (! is_numeric($course->center_id)) {
            throw ValidationException::withMessages([
                'course_id' => ['Course center is not configured.'],
            ]);
        }

        if ((int) $course->center_id !== $centerId) {
            throw ValidationException::withMessages([
                'course_id' => ['Course does not belong to the specified center.'],
            ]);
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $userIds)));
        $users = User::query()
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        $results = [
            'approved' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $userId) {
            $student = $users->get($userId);

            if (! $student instanceof User) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => 'Student not found.',
                ];

                continue;
            }

            try {
                $this->assertEnrollmentEligibility($student, $course, $admin);

                $active = Enrollment::query()
                    ->activeForUserAndCourse($student, $course)
                    ->first();

                if ($active !== null) {
                    $results['skipped'][] = $userId;

                    continue;
                }

                $pending = Enrollment::query()
                    ->pendingForUserAndCourse($student, $course)
                    ->first();

                if ($pending === null) {
                    $pending = Enrollment::create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'center_id' => $course->center_id,
                        'status' => EnrollmentStatus::Pending,
                        'enrolled_at' => Carbon::now(),
                    ]);

                    $this->auditLogService->logByType(
                        $student,
                        Enrollment::class,
                        (int) $pending->id,
                        AuditActions::ENROLLMENT_REQUEST_CREATED,
                        [
                            'course_id' => $course->id,
                            'center_id' => $course->center_id,
                        ]
                    );
                }

                $approved = $this->updateStatus($pending, EnrollmentStatus::Active->name, $admin);
                $this->sendEnrollmentNotification($approved);

                $results['approved'][] = $approved;
            } catch (ValidationException $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $this->formatValidationError($exception),
                ];
            } catch (\Throwable $exception) {
                $results['failed'][] = [
                    'user_id' => $userId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Manually send enrollment notification to a student.
     */
    public function sendEnrollmentNotification(Enrollment $enrollment): bool
    {
        return $this->notificationService->sendEnrollmentNotification($enrollment);
    }

    public function updateStatus(Enrollment $enrollment, string $status, ?User $actor = null): Enrollment
    {
        $statusValue = $this->normalizeStatus($status);

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $enrollment);
        }

        if ($enrollment->status === $statusValue) {
            return $enrollment;
        }

        $enrollment->status = $statusValue;
        $enrollment->save();

        $this->log(AuditActions::ENROLLMENT_STATUS_UPDATED, $actor, $enrollment->course, $enrollment, [
            'status' => $enrollment->statusLabel(),
        ]);

        return $enrollment->fresh(['course', 'user']) ?? $enrollment;
    }

    public function remove(Enrollment $enrollment, ?User $actor = null): void
    {
        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $enrollment);
        }

        $enrollment->delete();

        $this->log(AuditActions::ENROLLMENT_DELETED, $actor, $enrollment->course, $enrollment);
    }

    public function paginateForStudent(User $student, int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::query()
            ->forUser($student)
            ->notDeleted()
            ->with(['course', 'course.category', 'course.center'])
            ->orderByDesc('enrolled_at');

        if (is_numeric($student->center_id)) {
            $query->whereHas('course', function ($query) use ($student): void {
                $query->where('center_id', (int) $student->center_id);
            });
        } else {
            $query->whereHas('course.center', function ($query): void {
                $query->where('type', CenterType::Unbranded->value);
            });
        }

        return $query->paginate($perPage);
    }

    public function paginateForAdmin(User $admin, EnrollmentFilters $filters): LengthAwarePaginator
    {
        $query = Enrollment::query()
            ->with(['course', 'user', 'center'])
            ->orderByDesc('enrolled_at');

        // Scope to admin's accessible centers
        $centerIds = $this->centerScopeService->getAccessibleCenterIds($admin);
        if ($centerIds !== null) {
            $query->whereIn('center_id', $centerIds);
        }

        // Apply filters
        if ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }

        if ($filters->courseId !== null) {
            $query->where('course_id', $filters->courseId);
        }

        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->status !== null) {
            $statusValue = $this->normalizeStatus($filters->status);
            $query->where('status', $statusValue);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    public function assertAdminCanAccess(User $admin, Enrollment $enrollment): void
    {
        $this->centerScopeService->assertAdminSameCenter($admin, $enrollment);
    }

    public function getActiveEnrollment(User $student, Course $course): ?Enrollment
    {
        return Enrollment::query()
            ->activeForUserAndCourse($student, $course)
            ->first();
    }

    private function normalizeStatus(string $status): EnrollmentStatus
    {
        $value = strtoupper(trim($status));
        $map = [
            'ACTIVE' => EnrollmentStatus::Active,
            'DEACTIVATED' => EnrollmentStatus::Deactivated,
            'CANCELLED' => EnrollmentStatus::Cancelled,
        ];

        if (! array_key_exists($value, $map)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid enrollment status.'],
            ]);
        }

        return $map[$value];
    }

    private function assertEnrollmentEligibility(User $student, Course $course, ?User $actor = null): void
    {
        $this->studentAccessService->assertStudent(
            $student,
            null,
            null,
            403,
            ['user_id' => ['Enrollment can only be created for students.']]
        );

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        if (! is_numeric($course->center_id)) {
            throw ValidationException::withMessages([
                'course_id' => ['Course center is not configured.'],
            ]);
        }

        $centerId = (int) $course->center_id;
        $course->loadMissing('center');

        if (is_numeric($student->center_id) && (int) $student->center_id !== $centerId) {
            throw ValidationException::withMessages([
                'course_id' => ['Course does not belong to the student center.'],
            ]);
        }

        if (! is_numeric($student->center_id) && ($course->center?->type ?? CenterType::Branded) !== CenterType::Unbranded) {
            throw ValidationException::withMessages([
                'course_id' => ['Course is not available for system-level students.'],
            ]);
        }

        if (is_numeric($student->center_id) && ! $student->belongsToCenter($centerId)) {
            throw ValidationException::withMessages([
                'course_id' => ['Student does not belong to this center.'],
            ]);
        }
    }

    private function formatValidationError(ValidationException $exception): string
    {
        $errors = $exception->errors();
        if (! empty($errors)) {
            $messages = array_values($errors)[0] ?? [];
            if (! empty($messages)) {
                return (string) $messages[0];
            }
        }

        return 'Validation failed.';
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(string $action, ?User $actor, ?Course $course, Enrollment $enrollment, array $metadata = []): void
    {
        $payload = array_filter([
            'course_id' => $course?->id ?? $enrollment->course_id,
            'student_id' => $enrollment->user_id,
            ...$metadata,
        ]);

        $this->auditLogService->log($actor, $enrollment, $action, $payload);
    }
}
