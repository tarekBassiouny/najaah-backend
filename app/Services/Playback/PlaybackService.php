<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyEmbedTokenService;
use App\Services\Contracts\ViewLimitServiceInterface;
use App\Services\Playback\Contracts\PlaybackServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlaybackService implements PlaybackServiceInterface
{
    private const BUNNY_EMBED_BASE_URL = 'https://iframe.mediadelivery.net/embed';

    public function __construct(
        private readonly PlaybackAuthorizationService $authorizationService,
        private readonly BunnyEmbedTokenService $embedTokenService,
        private readonly ViewLimitServiceInterface $viewLimitService
    ) {}

    /**
     * @return array{
     *   library_id:string,
     *   video_uuid:string,
     *   session_id:int,
     *   embed_token:string,
     *   embed_token_expires_at:string,
     *   embed_token_expires:int,
     *   expires_in:int,
     *   expires_at:int,
     *   embed_url:string,
     *   is_locked:bool,
     *   remaining_views:int|null,
     *   view_limit:int|null
     * }
     */
    public function requestPlayback(User $student, Center $center, Course $course, Video $video): array
    {
        $this->authorizationService->assertCanStartPlayback($student, $center, $course, $video);
        $device = $this->authorizationService->getActiveDevice();

        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        $libraryId = config('bunny.api.library_id');
        if (! is_numeric($libraryId)) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        $enrollmentId = $this->resolveEnrollmentId($student, $course);
        $embedTokenTtl = $this->resolveEmbedTokenTtl();
        $embedTokenData = $this->embedTokenService->generate(
            $videoUuid,
            $student,
            $center->id,
            $enrollmentId,
            $embedTokenTtl
        );
        $embedTokenExpires = (int) $embedTokenData['expires'];
        $embedTokenExpiresAt = Carbon::createFromTimestamp($embedTokenExpires);
        $embedTokenExpiresIn = max(0, $embedTokenExpires - (int) now()->timestamp);

        $session = DB::transaction(function () use ($student, $video, $course, $enrollmentId, $device, $embedTokenData, $embedTokenExpiresAt): PlaybackSession {
            $now = now();

            PlaybackSession::where('user_id', $student->id)
                ->whereNull('ended_at')
                ->whereNull('deleted_at')
                ->where('expires_at', '<', $now)
                ->update(['ended_at' => $now]);

            /** @var PlaybackSession|null $active */
            $active = PlaybackSession::where('user_id', $student->id)
                ->whereNull('ended_at')
                ->whereNull('deleted_at')
                ->where('expires_at', '>', $now)
                ->first();

            if ($active instanceof PlaybackSession) {
                if ($active->device_id !== $device->id) {
                    throw new DomainException('Playback already active on another device.', ErrorCodes::CONCURRENT_DEVICE, 409);
                }

                $active->update(['ended_at' => $now]);
            }

            return PlaybackSession::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollmentId,
                'device_id' => $device->id,
                'embed_token' => $embedTokenData['token'],
                'embed_token_expires_at' => $embedTokenExpiresAt,
                'started_at' => $now,
                'expires_at' => $now->copy()->addSeconds((int) config('playback.session_ttl')),
                'last_activity_at' => $now,
                'progress_percent' => 0,
                'is_full_play' => false,
            ]);
        });

        $embedUrl = $this->buildEmbedUrl(
            (string) $libraryId,
            $videoUuid,
            $embedTokenData['token'],
            $embedTokenExpires
        );

        $remainingViews = $this->viewLimitService->getRemainingViews($student, $video, $course);
        $viewLimit = $this->viewLimitService->getEffectiveLimit($student, $video, $course);
        $isLocked = $this->viewLimitService->isLocked($student, $video, $course);

        return [
            'library_id' => (string) $libraryId,
            'video_uuid' => $videoUuid,
            'session_id' => $session->id,
            'embed_token' => $embedTokenData['token'],
            'embed_token_expires_at' => $session->embed_token_expires_at?->toIso8601String() ?? '',
            'embed_token_expires' => $embedTokenExpires,
            'expires_in' => $embedTokenExpiresIn,
            'expires_at' => $embedTokenExpires,
            'embed_url' => $embedUrl,
            'is_locked' => $isLocked,
            'remaining_views' => $remainingViews,
            'view_limit' => $viewLimit,
        ];
    }

    /**
     * @return array{
     *   session_id:int,
     *   embed_token:string,
     *   expires_in:int,
     *   expires_at:int,
     *   embed_url:string
     * }
     */
    public function refreshEmbedToken(User $student, Center $center, Course $course, Video $video, PlaybackSession $session): array
    {
        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        $libraryId = config('bunny.api.library_id');
        if (! is_numeric($libraryId)) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        $enrollmentId = $this->resolveEnrollmentId($student, $course);
        $tokenData = $this->embedTokenService->generate(
            $videoUuid,
            $student,
            $center->id,
            $enrollmentId,
            $this->resolveEmbedTokenTtl()
        );

        $tokenExpires = (int) $tokenData['expires'];
        $expiresAt = Carbon::createFromTimestamp($tokenExpires);
        $expiresIn = max(0, $tokenExpires - (int) now()->timestamp);

        $session->update([
            'embed_token' => $tokenData['token'],
            'embed_token_expires_at' => $expiresAt,
            'expires_at' => now()->addSeconds((int) config('playback.session_ttl')),
            'last_activity_at' => now(),
        ]);

        $embedUrl = $this->buildEmbedUrl(
            (string) $libraryId,
            $videoUuid,
            $tokenData['token'],
            $tokenExpires
        );

        return [
            'session_id' => $session->id,
            'embed_token' => $tokenData['token'],
            'expires_in' => $expiresIn,
            'embed_url' => $embedUrl,
            'expires_at' => $tokenExpires,
        ];
    }

    /**
     * @return array{progress:int,is_full_play:bool,is_locked:bool,remaining_views:int|null,view_limit:int|null}
     */
    public function updateProgress(User $student, PlaybackSession $session, int $percentage): array
    {
        if ($session->user_id !== $student->id) {
            return [
                'progress' => $session->progress_percent,
                'is_full_play' => $session->is_full_play,
                'is_locked' => $session->is_locked,
                'remaining_views' => null,
                'view_limit' => null,
            ];
        }

        if ($session->ended_at !== null) {
            return [
                'progress' => $session->progress_percent,
                'is_full_play' => $session->is_full_play,
                'is_locked' => $session->is_locked,
                'remaining_views' => null,
                'view_limit' => null,
            ];
        }

        $expiresAt = $session->expires_at;
        if ($expiresAt === null || $expiresAt->lte(now())) {
            return [
                'progress' => $session->progress_percent,
                'is_full_play' => $session->is_full_play,
                'is_locked' => $session->is_locked,
                'remaining_views' => null,
                'view_limit' => null,
            ];
        }

        if ($percentage <= $session->progress_percent) {
            $session->update([
                'last_activity_at' => now(),
                'expires_at' => now()->addSeconds((int) config('playback.session_ttl')),
            ]);

            $session->refresh();

            return [
                'progress' => $session->progress_percent,
                'is_full_play' => $session->is_full_play,
                'is_locked' => $session->is_locked,
                'remaining_views' => $this->viewLimitService->getRemainingViews($student, $session->video, $session->course),
                'view_limit' => $this->viewLimitService->getEffectiveLimit($student, $session->video, $session->course),
            ];
        }

        $threshold = (int) config('playback.full_play_threshold', 80);
        $isFullPlay = $percentage >= $threshold || $session->is_full_play;
        $becameFullPlay = $isFullPlay && ! $session->is_full_play;

        $session->update([
            'progress_percent' => $percentage,
            'is_full_play' => $isFullPlay,
            'last_activity_at' => now(),
            'expires_at' => now()->addSeconds((int) config('playback.session_ttl')),
        ]);

        // Check lock status after saving, so the current session's full play is counted
        if ($becameFullPlay) {
            $this->incrementVideoViewCount($session->video);

            $isLocked = $this->viewLimitService->isLocked($student, $session->video, $session->course);
            if ($isLocked) {
                $session->update(['is_locked' => true]);
            }
        }

        $session->refresh();

        return [
            'progress' => $session->progress_percent,
            'is_full_play' => $session->is_full_play,
            'is_locked' => $session->is_locked,
            'remaining_views' => $this->viewLimitService->getRemainingViews($student, $session->video, $session->course),
            'view_limit' => $this->viewLimitService->getEffectiveLimit($student, $session->video, $session->course),
        ];
    }

    /**
     * Close a playback session.
     */
    public function closeSession(int $sessionId, int $watchDuration, string $reason): void
    {
        $session = PlaybackSession::find($sessionId);

        if ($session === null || $session->ended_at !== null) {
            return;
        }

        $session->update([
            'ended_at' => now(),
            'watch_duration' => $watchDuration,
            'close_reason' => $reason,
            'auto_closed' => in_array($reason, ['timeout', 'max_views'], true),
        ]);
    }

    private function buildEmbedUrl(string $libraryId, string $videoUuid, string $token, int $expires): string
    {
        return sprintf(
            '%s/%s/%s?token=%s&expires=%d',
            self::BUNNY_EMBED_BASE_URL,
            $libraryId,
            $videoUuid,
            $token,
            $expires
        );
    }

    private function resolveEmbedTokenTtl(): int
    {
        $ttl = (int) config('bunny.embed_token_ttl', 240);
        if ($ttl <= 0) {
            $ttl = 240;
        }

        $max = (int) config('playback.embed_token_ttl_max', 300);
        $min = (int) config('playback.embed_token_ttl_min', 180);

        return min($max, max($min, $ttl));
    }

    private function resolveEnrollmentId(User $student, Course $course): int
    {
        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();

        if (! $enrollment instanceof Enrollment) {
            $this->deny(ErrorCodes::ENROLLMENT_REQUIRED, 'Active enrollment required.', 403);
        }

        return (int) $enrollment->id;
    }

    /**
     * Increment the cached view count on the video.
     */
    private function incrementVideoViewCount(Video $video): void
    {
        $video->increment('views_count');
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
