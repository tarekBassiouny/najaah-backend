<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Enums\MediaSourceType;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Bunny\BunnyEmbedTokenService;
use App\Services\Playback\Contracts\PlaybackServiceInterface;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlaybackService implements PlaybackServiceInterface
{
    private const BUNNY_EMBED_BASE_URL = 'https://iframe.mediadelivery.net/embed';

    public function __construct(
        private readonly PlaybackAuthorizationService $authorizationService,
        private readonly BunnyEmbedTokenService $embedTokenService,
        private readonly ViewLimitServiceInterface $viewLimitService,
        private readonly EnrollmentAccessService $enrollmentAccessService
    ) {}

    /**
     * @return array{
     *   source_type:int,
     *   source_provider:string,
     *   source_url:string|null,
     *   source_id:string|null,
     *   library_id:string|null,
     *   video_uuid:string|null,
     *   embed_token:string|null,
     *   embed_token_expires_at:string|null,
     *   embed_token_expires:int|null,
     *   session_id:int,
     *   session_expires_at:string,
     *   session_expires_in:int,
     *   embed_url:string|null,
     *   is_locked:bool,
     *   remaining_views:int|null,
     *   view_limit:int|null
     * }
     */
    public function requestPlayback(User $student, Center $center, Course $course, Video $video): array
    {
        $this->authorizationService->assertCanStartPlayback($student, $center, $course, $video);
        $device = $this->authorizationService->getActiveDevice();

        $enrollmentId = $this->resolveEnrollmentId($student, $course);

        if ($video->source_type === MediaSourceType::Url) {
            $sourceUrl = $video->source_url;
            if (! is_string($sourceUrl) || $sourceUrl === '') {
                $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
            }

            $session = $this->createPlaybackSession($student, $video, $course, $device, $enrollmentId);
            $sessionExpiresAt = $session->expires_at;
            $sessionExpiresIn = $sessionExpiresAt !== null
                ? max(0, (int) $sessionExpiresAt->timestamp - (int) now()->timestamp)
                : 0;

            return [
                'source_type' => $video->source_type->value,
                'source_provider' => $video->source_provider,
                'source_url' => $sourceUrl,
                'source_id' => $video->source_id,
                'library_id' => $video->library_id !== null ? (string) $video->library_id : null,
                'video_uuid' => $video->source_id,
                'embed_token' => null,
                'embed_token_expires_at' => null,
                'embed_token_expires' => null,
                'session_id' => $session->id,
                'session_expires_at' => $sessionExpiresAt?->toIso8601String() ?? '',
                'session_expires_in' => $sessionExpiresIn,
                'embed_url' => null,
                'is_locked' => $this->viewLimitService->isLocked($student, $video, $course),
                'remaining_views' => $this->viewLimitService->getRemainingViews($student, $video, $course),
                'view_limit' => $this->viewLimitService->getEffectiveLimit($student, $video, $course),
            ];
        }

        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

        /** @var string $videoUuid */
        $libraryId = config('bunny.api.library_id');
        if (! is_numeric($libraryId)) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for playback.', 422);
        }

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

        $session = $this->createPlaybackSession($student, $video, $course, $device, $enrollmentId, $embedTokenData, $embedTokenExpiresAt);

        $embedUrl = $this->buildEmbedUrl(
            (string) $libraryId,
            $videoUuid,
            $embedTokenData['token'],
            $embedTokenExpires
        );

        $remainingViews = $this->viewLimitService->getRemainingViews($student, $video, $course);
        $viewLimit = $this->viewLimitService->getEffectiveLimit($student, $video, $course);
        $isLocked = $this->viewLimitService->isLocked($student, $video, $course);

        $sessionExpiresAt = $session->expires_at;
        $sessionExpiresIn = $sessionExpiresAt !== null
            ? max(0, (int) $sessionExpiresAt->timestamp - (int) now()->timestamp)
            : 0;

        return [
            'library_id' => (string) $libraryId,
            'video_uuid' => $videoUuid,
            'session_id' => $session->id,
            'embed_token' => $embedTokenData['token'],
            'embed_token_expires_at' => $session->embed_token_expires_at?->toIso8601String() ?? '',
            'embed_token_expires' => $embedTokenExpires,
            'session_expires_at' => $sessionExpiresAt?->toIso8601String() ?? '',
            'session_expires_in' => $sessionExpiresIn,
            'embed_url' => $embedUrl,
            'is_locked' => $isLocked,
            'remaining_views' => $remainingViews,
            'view_limit' => $viewLimit,
            'source_type' => $video->source_type->value,
            'source_provider' => $video->source_provider,
            'source_url' => $video->source_url,
            'source_id' => $video->source_id,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $embedTokenData
     */
    private function createPlaybackSession(
        User $student,
        Video $video,
        Course $course,
        UserDevice $device,
        int $enrollmentId,
        ?array $embedTokenData = null,
        ?\DateTimeInterface $embedTokenExpiresAt = null
    ): PlaybackSession {
        return DB::transaction(function () use ($student, $video, $course, $device, $enrollmentId, $embedTokenData, $embedTokenExpiresAt): PlaybackSession {
            $now = now();

            PlaybackSession::query()
                ->forUser($student)
                ->active()
                ->notDeleted()
                ->where('expires_at', '<', $now)
                ->update(['ended_at' => $now]);

            /** @var PlaybackSession|null $active */
            $active = PlaybackSession::query()
                ->forUser($student)
                ->active()
                ->notDeleted()
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
                'embed_token' => $embedTokenData['token'] ?? null,
                'embed_token_expires_at' => $embedTokenExpiresAt,
                'started_at' => $now,
                'expires_at' => $now->copy()->addSeconds((int) config('playback.session_ttl')),
                'last_activity_at' => $now,
                'progress_percent' => 0,
                'is_full_play' => false,
            ]);
        });
    }

    /**
     * @return array{
     *   session_id:int,
     *   embed_token:string,
     *   embed_token_expires:int,
     *   embed_token_expires_at:string,
     *   session_expires_at:string,
     *   session_expires_in:int,
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
        $embedTokenExpiresAt = Carbon::createFromTimestamp($tokenExpires);
        $sessionExpiresAt = now()->addSeconds((int) config('playback.session_ttl'));

        $session->update([
            'embed_token' => $tokenData['token'],
            'embed_token_expires_at' => $embedTokenExpiresAt,
            'expires_at' => $sessionExpiresAt,
            'last_activity_at' => now(),
        ]);

        $embedUrl = $this->buildEmbedUrl(
            (string) $libraryId,
            $videoUuid,
            $tokenData['token'],
            $tokenExpires
        );

        $sessionExpiresIn = max(0, (int) $sessionExpiresAt->timestamp - (int) now()->timestamp);

        return [
            'session_id' => $session->id,
            'embed_token' => $tokenData['token'],
            'embed_token_expires' => $tokenExpires,
            'embed_token_expires_at' => $embedTokenExpiresAt->toIso8601String(),
            'session_expires_at' => $sessionExpiresAt->toIso8601String(),
            'session_expires_in' => $sessionExpiresIn,
            'embed_url' => $embedUrl,
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
            if ($session->auto_closed) {
                $this->reopenSession($session);
            } else {
                return $this->buildProgressPayload($session, $student, false);
            }
        }

        if ($percentage <= $session->progress_percent) {
            $session->update([
                'last_activity_at' => now(),
                'expires_at' => now()->addSeconds((int) config('playback.session_ttl')),
            ]);

            $session->refresh();

            return $this->buildProgressPayload($session, $student);
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

        return $this->buildProgressPayload($session, $student);
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
        $enrollment = $this->enrollmentAccessService->assertActiveEnrollment($student, $course);

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
     * @return array{progress:int,is_full_play:bool,is_locked:bool,remaining_views:int|null,view_limit:int|null}
     */
    private function buildProgressPayload(PlaybackSession $session, User $student, bool $includeViews = true): array
    {
        return [
            'progress' => $session->progress_percent,
            'is_full_play' => $session->is_full_play,
            'is_locked' => $session->is_locked,
            'remaining_views' => $includeViews ? $this->viewLimitService->getRemainingViews($student, $session->video, $session->course) : null,
            'view_limit' => $includeViews ? $this->viewLimitService->getEffectiveLimit($student, $session->video, $session->course) : null,
        ];
    }

    private function reopenSession(PlaybackSession $session): void
    {
        $ttl = (int) config('playback.session_ttl');
        $session->update([
            'watch_duration' => $session->watch_duration ?? 0,
            'close_reason' => null,
            'ended_at' => null,
            'auto_closed' => false,
            'expires_at' => now()->addSeconds($ttl),
            'last_activity_at' => now(),
        ]);
        $session->refresh();
    }

    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
