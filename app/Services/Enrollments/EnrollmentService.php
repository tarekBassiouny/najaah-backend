<?php

declare(strict_types=1);

namespace App\Services\Enrollments;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    public function enroll(User $student, Course $course, string $status, ?User $actor = null): Enrollment
    {
        $this->assertStudent($student);
        $statusValue = $this->normalizeStatus($status);

        if ($actor instanceof User) {
            $this->centerScopeService->assertAdminSameCenter($actor, $course);
        }

        if (! is_numeric($course->center_id)) {
            throw ValidationException::withMessages([
                'course_id' => ['Course center is not configured.'],
            ]);
        }

        $centerId = (int) $course->center_id;

        if (! $student->belongsToCenter($centerId)) {
            $student->centers()->syncWithoutDetaching([
                $centerId => ['type' => 'student'],
            ]);

            if ($student->center_id === null) {
                $student->center_id = $centerId;
                $student->save();
            }
        }

        return DB::transaction(function () use ($student, $course, $statusValue, $actor): Enrollment {
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

            $this->log('enrollment_created', $actor, $course, $enrollment);

            return $enrollment->fresh(['course', 'user']) ?? $enrollment;
        });
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

        $this->log('enrollment_status_updated', $actor, $enrollment->course, $enrollment, [
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

        $this->log('enrollment_deleted', $actor, $enrollment->course, $enrollment);
    }

    public function paginateForStudent(User $student, int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::query()
            ->where('user_id', $student->id)
            ->whereNull('deleted_at')
            ->with(['course', 'course.category', 'course.center'])
            ->orderByDesc('enrolled_at')
            ->paginate($perPage);
    }

    public function getActiveEnrollment(User $student, Course $course): ?Enrollment
    {
        return Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();
    }

    private function normalizeStatus(string $status): int
    {
        $value = strtoupper(trim($status));
        $map = [
            'ACTIVE' => Enrollment::STATUS_ACTIVE,
            'DEACTIVATED' => Enrollment::STATUS_DEACTIVATED,
            'CANCELLED' => Enrollment::STATUS_CANCELLED,
        ];

        if (! array_key_exists($value, $map)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid enrollment status.'],
            ]);
        }

        return $map[$value];
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            throw ValidationException::withMessages([
                'user_id' => ['Enrollment can only be created for students.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(string $action, ?User $actor, ?Course $course, Enrollment $enrollment, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => Enrollment::class,
            'entity_id' => $enrollment->id,
            'metadata' => array_filter([
                'course_id' => $course?->id ?? $enrollment->course_id,
                'student_id' => $enrollment->user_id,
                ...$metadata,
            ]),
        ]);
    }
}
