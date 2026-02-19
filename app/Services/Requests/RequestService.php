<?php

declare(strict_types=1);

namespace App\Services\Requests;

use App\Enums\CenterType;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\ExtraViewRequestStatus;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Playback\ViewLimitService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        private readonly ViewLimitService $viewLimitService,
        private readonly StudentAccessService $studentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function createExtraViewRequest(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        ?string $reason
    ): void {
        $this->studentAccessService->assertStudent($student);
        $this->assertCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);

        DB::transaction(function () use ($student, $course, $video, $reason): void {
            $pivot = $this->courseAccessService->getVideoPivotOrFail($course, $video);
            $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

            $pending = ExtraViewRequest::query()
                ->pendingForUserAndVideo($student, $video)
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending request already exists for this video.', 422);
            }

            $remaining = $this->viewLimitService->remaining(
                $student,
                $video,
                $course,
                $pivot->pivot?->view_limit_override
            );

            if ($remaining > 0) {
                $this->deny(ErrorCodes::VIEWS_AVAILABLE, 'Extra views are not allowed while views remain.', 422);
            }

            $request = ExtraViewRequest::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'status' => ExtraViewRequestStatus::Pending,
                'reason' => $reason,
            ]);

            $this->auditLogService->logByType($student, ExtraViewRequest::class, (int) $request->id, AuditActions::EXTRA_VIEW_REQUEST_CREATED, [
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
            ]);
        });
    }

    public function createEnrollmentRequest(
        User $student,
        Center $center,
        Course $course,
        ?string $reason
    ): void {
        $this->studentAccessService->assertStudent($student);
        $this->assertCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);

        DB::transaction(function () use ($student, $course): void {
            $enrolled = $this->enrollmentAccessService->hasActiveEnrollment($student, $course, true);

            if ($enrolled) {
                $this->deny(ErrorCodes::ALREADY_ENROLLED, 'Student is already enrolled.', 422);
            }

            $pending = Enrollment::query()
                ->pendingForUserAndCourse($student, $course)
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending enrollment request already exists.', 422);
            }

            $enrollment = Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'status' => EnrollmentStatus::Pending,
                'enrolled_at' => Carbon::now(),
            ]);

            $this->auditLogService->logByType($student, Enrollment::class, (int) $enrollment->id, AuditActions::ENROLLMENT_REQUEST_CREATED, [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
            ]);
        });
    }

    public function createDeviceChangeRequest(User $student, ?string $reason): void
    {
        $this->studentAccessService->assertStudent($student);

        DB::transaction(function () use ($student, $reason): void {
            /** @var UserDevice|null $active */
            $active = UserDevice::query()
                ->activeForUser($student)
                ->notDeleted()
                ->lockForUpdate()
                ->first();

            if ($active === null) {
                $this->deny(ErrorCodes::NO_ACTIVE_DEVICE, 'Active device required to request a change.', 422);
            }

            $pending = DeviceChangeRequest::query()
                ->forUser($student)
                ->pending()
                ->notDeleted()
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending device change request already exists.', 422);
            }

            $request = DeviceChangeRequest::create([
                'user_id' => $student->id,
                'center_id' => $student->center_id,
                'current_device_id' => $active->device_id,
                'new_device_id' => '',
                'new_model' => '',
                'new_os_version' => '',
                'status' => DeviceChangeRequestStatus::Pending,
                'reason' => $reason,
            ]);

            $this->auditLogService->logByType($student, DeviceChangeRequest::class, (int) $request->id, AuditActions::DEVICE_CHANGE_REQUEST_CREATED, [
                'center_id' => $student->center_id,
                'old_device_id' => $active->device_id,
            ]);
        });
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }

    private function assertCenterAccess(User $student, Center $center): void
    {
        if ($center->status !== Center::STATUS_ACTIVE) {
            $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
        }

        if (is_numeric($student->center_id)) {
            if ((int) $student->center_id !== (int) $center->id) {
                $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
            }

            return;
        }

        if ($center->type !== CenterType::Unbranded) {
            $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
        }
    }
}
