<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyEmbedTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class PlaybackService
{
    public function __construct(
        private readonly PlaybackAuthorizationService $authorizationService,
        private readonly BunnyEmbedTokenService $embedTokenService
    ) {}

    /**
     * @return array{library_id:string,video_uuid:string,embed_token:string,session_id:string}
     */
    public function requestPlayback(User $student, Center $center, Course $course, Video $video): array
    {
        $this->authorizationService->assertCanStartPlayback($student, $center, $course, $video);
        $device = $this->authorizationService->getActiveDevice();

        $session = DB::transaction(function () use ($student, $video, $device): PlaybackSession {
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
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'CONCURRENT_DEVICE',
                            'message' => 'Playback already active on another device.',
                        ],
                    ], 409));
                }

                $active->update(['ended_at' => $now]);
            }

            return PlaybackSession::create([
                'user_id' => $student->id,
                'video_id' => $video->id,
                'device_id' => $device->id,
                'started_at' => $now,
                'expires_at' => $now->copy()->addSeconds(config('playback.session_ttl')),
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

        $embedToken = $this->embedTokenService->generate(
            $videoUuid,
            $student,
            $this->resolveEmbedTokenTtl()
        );

        return [
            'library_id' => (string) $libraryId,
            'video_uuid' => $videoUuid,
            'embed_token' => $embedToken['token'],
            'session_id' => (string) $session->id,
        ];
    }

    public function updateProgress(User $student, PlaybackSession $session, int $percentage): void
    {
        if ($session->user_id !== $student->id) {
            return;
        }

        if ($session->ended_at !== null) {
            return;
        }

        $expiresAt = $session->expires_at;
        if ($expiresAt === null || $expiresAt->lte(now())) {
            return;
        }

        if ($percentage <= $session->progress_percent) {
            return;
        }

        $session->update([
            'progress_percent' => $percentage,
            'is_full_play' => $percentage >= 50 || $session->is_full_play,
            'expires_at' => now()->addSeconds(config('playback.session_ttl')),
        ]);
    }

    private function resolveEmbedTokenTtl(): int
    {
        $ttl = (int) config('bunny.embed_token_ttl', 600);
        if ($ttl <= 0) {
            $ttl = 600;
        }

        return min(600, max(300, $ttl));
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
