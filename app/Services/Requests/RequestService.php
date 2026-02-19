<?php

declare(strict_types=1);

namespace App\Services\Requests;

use App\Enums\CenterType;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\AdminNotifications\AdminNotificationDispatcher;
use App\Services\Audit\AuditLogService;
use App\Services\Playback\ExtraViewRequestService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        private readonly StudentAccessService $studentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly AuditLogService $auditLogService,
        private readonly AdminNotificationDispatcher $notificationDispatcher,
        private readonly ExtraViewRequestService $extraViewRequestService
    ) {}

    public function createExtraViewRequest(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        ?string $reason
    ): void {
        $this->extraViewRequestService->createForStudent($student, $center, $course, $video, $reason);
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

        DB::transaction(function () use ($student, $course, $reason): void {
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
                'reason' => $reason,
                'enrolled_at' => Carbon::now(),
            ]);

            $this->auditLogService->logByType($student, Enrollment::class, (int) $enrollment->id, AuditActions::ENROLLMENT_REQUEST_CREATED, [
                'course_id' => $course->id,
                'center_id' => $course->center_id,
            ]);

            $this->notificationDispatcher->dispatchEnrollmentRequest($enrollment->loadMissing(['user', 'course']));
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

            $this->notificationDispatcher->dispatchDeviceChangeRequest($request->loadMissing('user'));
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
