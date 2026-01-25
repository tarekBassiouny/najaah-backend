<?php

declare(strict_types=1);

namespace App\Services\Requests;

use App\Exceptions\DomainException;
use App\Exceptions\UnauthorizedException;
use App\Models\Center;
use App\Models\Course;
use App\Models\DeviceChangeRequest;
use App\Models\Enrollment;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Playback\ViewLimitService;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(private readonly ViewLimitService $viewLimitService) {}

    public function createExtraViewRequest(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        ?string $reason
    ): void {
        $this->assertStudent($student);
        $this->assertCourseInCenter($center, $course);

        DB::transaction(function () use ($student, $course, $video, $reason): void {
            $pivot = $course->videos()
                ->where('videos.id', $video->id)
                ->wherePivotNull('deleted_at')
                ->first();

            if ($pivot === null) {
                $this->deny(ErrorCodes::VIDEO_NOT_IN_COURSE, 'Video not available for this course.', 404);
            }

            $enrollment = Enrollment::where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->first();

            if ($enrollment === null) {
                $this->deny(ErrorCodes::ENROLLMENT_REQUIRED, 'Active enrollment required.', 403);
            }

            $pending = ExtraViewRequest::where('user_id', $student->id)
                ->where('video_id', $video->id)
                ->where('status', ExtraViewRequest::STATUS_PENDING)
                ->whereNull('deleted_at')
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

            ExtraViewRequest::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'status' => ExtraViewRequest::STATUS_PENDING,
                'reason' => $reason,
            ]);
        });
    }

    public function createEnrollmentRequest(
        User $student,
        Center $center,
        Course $course,
        ?string $reason
    ): void {
        $this->assertStudent($student);
        $this->assertCourseInCenter($center, $course);

        DB::transaction(function () use ($student, $course): void {
            $enrollment = Enrollment::where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($enrollment !== null) {
                $this->deny(ErrorCodes::ALREADY_ENROLLED, 'Student is already enrolled.', 422);
            }

            $pending = Enrollment::where('user_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', Enrollment::STATUS_PENDING)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending enrollment request already exists.', 422);
            }

            Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'status' => Enrollment::STATUS_PENDING,
                'enrolled_at' => Carbon::now(),
            ]);
        });
    }

    public function createDeviceChangeRequest(User $student, ?string $reason): void
    {
        $this->assertStudent($student);

        DB::transaction(function () use ($student, $reason): void {
            /** @var UserDevice|null $active */
            $active = UserDevice::where('user_id', $student->id)
                ->where('status', UserDevice::STATUS_ACTIVE)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if ($active === null) {
                $this->deny(ErrorCodes::NO_ACTIVE_DEVICE, 'Active device required to request a change.', 422);
            }

            $pending = DeviceChangeRequest::where('user_id', $student->id)
                ->where('status', DeviceChangeRequest::STATUS_PENDING)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending device change request already exists.', 422);
            }

            DeviceChangeRequest::create([
                'user_id' => $student->id,
                'center_id' => $student->center_id,
                'current_device_id' => $active->device_id,
                'new_device_id' => '',
                'new_model' => '',
                'new_os_version' => '',
                'status' => DeviceChangeRequest::STATUS_PENDING,
                'reason' => $reason,
            ]);
        });
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            throw new UnauthorizedException('Only students can perform this action.', 403);
        }
    }

    private function assertCourseInCenter(Center $center, Course $course): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Course not found.', 404);
        }
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
