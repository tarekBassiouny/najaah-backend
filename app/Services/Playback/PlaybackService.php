<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class PlaybackService
{
    public function __construct(
        private readonly ViewLimitService $viewLimitService
    ) {}

    /**
     * @return array{library_id:string,video_uuid:string,embed_token:string,session_id:string}
     */
    public function requestPlayback(User $student, Center $center, Course $course, Video $video): array
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

        $enrolled = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->exists();

        if (! $enrolled) {
            $this->deny('ENROLLMENT_REQUIRED', 'Active enrollment required.', 403);
        }

        $override = $pivot->pivot?->view_limit_override;
        $this->viewLimitService->assertWithinLimit($student, $video, $course, $override);

        $activeSession = PlaybackSession::query()
            ->where('user_id', $student->id)
            ->where('video_id', $video->id)
            ->whereNull('ended_at')
            ->whereNull('deleted_at')
            ->exists();

        if ($activeSession) {
            $this->deny('ACTIVE_SESSION_EXISTS', 'Active playback session already exists.', 409);
        }

        $device = $this->resolveActiveDevice($student);

        $session = DB::transaction(function () use ($student, $video, $device): PlaybackSession {
            return PlaybackSession::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'device_id' => $device->id,
                'started_at' => now(),
                'progress_percent' => 0,
                'is_full_play' => false,
            ]);
        });

        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.', 422);
        }

        $libraryId = $video->library_id ?? $center->bunny_library_id;
        if (! is_numeric($libraryId)) {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.', 422);
        }

        return [
            'library_id' => (string) $libraryId,
            'video_uuid' => $videoUuid,
            'embed_token' => $this->generateEmbedToken($videoUuid, $student),
            'session_id' => (string) $session->id,
        ];
    }

    public function updateProgress(User $student, PlaybackSession $session, int $percentage): void
    {
        $this->assertStudent($student);

        if ($session->user_id !== $student->id) {
            $this->deny('UNAUTHORIZED', 'Session does not belong to the user.', 403);
        }

        if ($session->ended_at !== null) {
            $this->deny('SESSION_ENDED', 'Playback session has ended.', 409);
        }

        if ($percentage <= $session->progress_percent) {
            return;
        }

        $session->progress_percent = $percentage;

        if ($percentage >= 50 && ! $session->is_full_play) {
            $session->is_full_play = true;
        }

        $session->save();
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny('UNAUTHORIZED', 'Only students can access this endpoint.', 403);
        }
    }

    private function assertCenterAccess(User $student, Center $center): void
    {
        if (is_numeric($student->center_id)) {
            if ((int) $student->center_id !== (int) $center->id) {
                $this->deny('CENTER_MISMATCH', 'Center mismatch.', 403);
            }

            return;
        }

        if ((int) $center->type !== 0) {
            $this->deny('CENTER_MISMATCH', 'Center mismatch.', 403);
        }
    }

    private function assertCourseInCenter(Course $course, Center $center): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            $this->notFound('Course not found.');
        }
    }

    private function assertVideoReady(Video $video): void
    {
        if ((int) $video->encoding_status !== 3 || (int) $video->lifecycle_status !== 2) {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.', 422);
        }

        $session = $video->uploadSession;
        if ($session !== null && (int) $session->upload_status !== 3) {
            $this->deny('VIDEO_NOT_READY', 'Video is not ready for playback.', 422);
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
            $this->deny('NO_ACTIVE_DEVICE', 'Active device required for playback.', 422);
        }

        return $device;
    }

    private function generateEmbedToken(string $videoUuid, User $student): string
    {
        $secret = config('bunny.api.api_key');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Missing Bunny Stream API key.');
        }

        $expiresAt = now()->addMinutes(10)->timestamp;
        $payload = $videoUuid.'|'.$student->id.'|'.$expiresAt;
        $hash = hash_hmac('sha256', $payload, $secret);

        $token = base64_encode($payload.'|'.$hash);

        return rtrim(strtr($token, '+/', '-_'), '=');
    }

    private function notFound(string $message): void
    {
        $this->deny('NOT_FOUND', $message, 404);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status));
    }
}
