<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Enums\UserDeviceStatus;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Access\StudentAccessService;
use App\Services\Access\VideoAccessService;
use App\Services\Playback\Contracts\PlaybackAuthorizationServiceInterface;
use App\Support\ErrorCodes;

class PlaybackAuthorizationService implements PlaybackAuthorizationServiceInterface
{
    private ?UserDevice $activeDevice = null;

    public function __construct(
        private readonly ViewLimitService $viewLimitService,
        private readonly StudentAccessService $studentAccessService,
        private readonly CourseAccessService $courseAccessService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly VideoAccessService $videoAccessService
    ) {}

    public function assertCanStartPlayback(User $student, Center $center, Course $course, Video $video): void
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can access this endpoint.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->assertCenterAccess($student, $center);
        $this->courseAccessService->assertCourseInCenter($course, $center);

        $pivot = $this->courseAccessService->getVideoPivotOrFail(
            $course,
            $video,
            'Video not available for this course.',
            ErrorCodes::NOT_FOUND,
            404
        );

        if ($course->status !== CourseStatus::Published || $course->is_published !== true) {
            $this->notFound('Course not found.');
        }

        $this->videoAccessService->assertReadyForPlayback($video);
        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

        $override = $pivot->pivot?->view_limit_override;
        $this->viewLimitService->assertWithinLimit($student, $video, $course, $override);

        $this->activeDevice = $this->resolveActiveDevice($student);
    }

    public function assertCanRefreshToken(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can access this endpoint.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->assertSessionExists($session);

        if ($session->ended_at !== null) {
            $this->deny(ErrorCodes::SESSION_ENDED, 'Playback session has ended.', 409);
        }

        if ($session->user_id !== $student->id) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Session does not belong to the user.', 403);
        }

        $this->assertCourseContext($student, $center, $course, $video, $session);
        $this->videoAccessService->assertReadyForPlayback($video);
        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);
        $this->assertVideoUuid($video);
    }

    public function assertCanUpdateProgress(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void {
        $this->assertSessionExists($session);
        $this->assertCourseContext($student, $center, $course, $video, $session);
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can access this endpoint.',
            ErrorCodes::UNAUTHORIZED,
            403
        );

        if ($session->user_id !== $student->id) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Session does not belong to the user.', 403);
        }

        if ($session->ended_at !== null) {
            $this->deny(ErrorCodes::SESSION_ENDED, 'Playback session has ended.', 409);
        }

        if ($session->expires_at !== null && $session->expires_at->lte(now())) {
            $this->deny(ErrorCodes::SESSION_EXPIRED, 'Playback session has expired.', 409);
        }

        $this->videoAccessService->assertReadyForPlayback($video);
        $this->enrollmentAccessService->assertActiveEnrollment($student, $course);
    }

    public function getActiveDevice(): UserDevice
    {
        if (! $this->activeDevice instanceof UserDevice) {
            throw new \RuntimeException('Active device has not been resolved.');
        }

        return $this->activeDevice;
    }

    private function assertCenterAccess(User $student, Center $center): void
    {
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

    private function assertCourseContext(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void {
        $this->courseAccessService->assertCourseInCenter($course, $center);

        $videoInCourse = $this->courseAccessService->isVideoInCourse($course, $video);

        if (! $videoInCourse || $session->video_id !== $video->id) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Video not found.', 404);
        }

        $this->assertCenterAccess($student, $center);
    }

    private function assertVideoUuid(Video $video): void
    {
        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }
    }

    private function assertSessionExists(PlaybackSession $session): void
    {
        if (! $session->exists) {
            $this->deny(ErrorCodes::SESSION_NOT_FOUND, 'Playback session not found.', 404);
        }
    }

    private function resolveActiveDevice(User $student): UserDevice
    {
        // First, try to get the authenticated device from the request (set by JWT middleware)
        $authenticatedDevice = request()->attributes->get('authenticated_device');

        if ($authenticatedDevice instanceof UserDevice) {
            // Verify the device belongs to this student
            if ((int) $authenticatedDevice->user_id !== (int) $student->id) {
                $this->deny(ErrorCodes::DEVICE_MISMATCH, 'Device does not belong to the user.', 403);
            }

            // Verify the device is still active
            if ($authenticatedDevice->status !== UserDeviceStatus::Active) {
                $this->deny(ErrorCodes::DEVICE_REVOKED, 'Device has been revoked.', 403);
            }

            return $authenticatedDevice;
        }

        // Fallback: Query for active device (for backwards compatibility or tests)
        /** @var UserDevice|null $device */
        $device = UserDevice::query()
            ->activeForUser($student)
            ->notDeleted()
            ->first();

        if ($device === null) {
            $this->deny(ErrorCodes::NO_ACTIVE_DEVICE, 'Active device required for playback.', 422);
        }

        return $device;
    }

    private function notFound(string $message): void
    {
        $this->deny(ErrorCodes::NOT_FOUND, $message, 404);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
