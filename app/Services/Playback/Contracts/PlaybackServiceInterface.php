<?php

declare(strict_types=1);

namespace App\Services\Playback\Contracts;

use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;

interface PlaybackServiceInterface
{
    /**
     * @return array{
     *   library_id: string,
     *   video_uuid: string,
     *   session_id: int,
     *   embed_token: string,
     *   embed_token_expires_at: string,
     *   embed_token_expires: int,
     *   expires_in: int,
     *   expires_at: int,
     *   embed_url: string,
     *   is_locked: bool,
     *   remaining_views: int|null,
     *   view_limit: int|null
     * }
     */
    public function requestPlayback(User $student, Center $center, Course $course, Video $video): array;

    /**
     * @return array{
     *   session_id: int,
     *   embed_token: string,
     *   expires_in: int,
     *   expires_at: int,
     *   embed_url: string
     * }
     */
    public function refreshEmbedToken(User $student, Center $center, Course $course, Video $video, PlaybackSession $session): array;

    /**
     * @return array{progress: int, is_full_play: bool, is_locked: bool, remaining_views: int|null, view_limit: int|null}
     */
    public function updateProgress(User $student, PlaybackSession $session, int $percentage): array;

    public function closeSession(int $sessionId, int $watchDuration, string $reason): void;
}
