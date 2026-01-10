<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Support\ErrorCodes;

class PlaybackAuthorizationService
{
    private ?UserDevice $activeDevice = null;

    public function __construct(private readonly ViewLimitService $viewLimitService) {}

    public function assertCanStartPlayback(User $student, Center $center, Course $course, Video $video): void
    {
        $this->assertStudent($student);
        $this->assertCenterAccess($student, $center);
        $this->assertCourseInCenter($course, $center);

        $pivot = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->first();

        if ($pivot === null) {
            $this->notFound('Video not available for this course.');
        }

        if ((int) $course->status !== 3 || $course->is_published !== true) {
            $this->notFound('Course not found.');
        }

        $this->assertVideoReady($video);
        $this->assertEnrollmentActive($student, $course);

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
        $this->assertStudent($student);
        $this->assertSessionExists($session);

        if ($session->ended_at !== null) {
            $this->deny(ErrorCodes::SESSION_ENDED, 'Playback session has ended.', 409);
        }

        if ($session->user_id !== $student->id) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Session does not belong to the user.', 403);
        }

        $this->assertCourseContext($student, $center, $course, $video, $session);
        $this->assertVideoReady($video);
        $this->assertEnrollmentActive($student, $course);
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
        $this->assertStudent($student);

        if ($session->user_id !== $student->id) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Session does not belong to the user.', 403);
        }

        if ($session->ended_at !== null) {
            $this->deny(ErrorCodes::SESSION_ENDED, 'Playback session has ended.', 409);
        }

        $this->assertVideoReady($video);
        $this->assertEnrollmentActive($student, $course);
    }

    public function getActiveDevice(): UserDevice
    {
        if (! $this->activeDevice instanceof UserDevice) {
            throw new \RuntimeException('Active device has not been resolved.');
        }

        return $this->activeDevice;
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only students can access this endpoint.', 403);
        }
    }

    private function assertCenterAccess(User $student, Center $center): void
    {
        if (is_numeric($student->center_id)) {
            if ((int) $student->center_id !== (int) $center->id) {
                $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
            }

            return;
        }

        if ((int) $center->type !== 0) {
            $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.', 403);
        }
    }

    private function assertCourseInCenter(Course $course, Center $center): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            $this->notFound('Course not found.');
        }
    }

    private function assertCourseContext(
        User $student,
        Center $center,
        Course $course,
        Video $video,
        PlaybackSession $session
    ): void {
        $this->assertCourseInCenter($course, $center);

        $videoInCourse = $course->videos()
            ->where('videos.id', $video->id)
            ->wherePivotNull('deleted_at')
            ->exists();

        if (! $videoInCourse || $session->video_id !== $video->id) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Video not found.', 404);
        }

        $this->assertCenterAccess($student, $center);
    }

    private function assertVideoReady(Video $video): void
    {
        if ((int) $video->encoding_status !== 3 || (int) $video->lifecycle_status !== 2) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        $session = $video->uploadSession;
        if ($session !== null && (int) $session->upload_status !== 3) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }
    }

    private function assertEnrollmentActive(User $student, Course $course): void
    {
        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->exists();

        if (! $enrolled) {
            $this->deny(ErrorCodes::ENROLLMENT_REQUIRED, 'Active enrollment required.', 403);
        }
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
        /** @var UserDevice|null $device */
        $device = UserDevice::query()
            ->where('user_id', $student->id)
            ->where('status', UserDevice::STATUS_ACTIVE)
            ->whereNull('deleted_at')
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
