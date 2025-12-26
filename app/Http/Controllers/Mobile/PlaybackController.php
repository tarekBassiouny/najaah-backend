<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\PlaybackProgressRequest;
use App\Http\Requests\Mobile\RefreshPlaybackTokenRequest;
use App\Http\Requests\Mobile\RequestPlaybackRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyEmbedTokenService;
use App\Services\Playback\PlaybackAuthorizationService;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;

class PlaybackController extends Controller
{
    public function __construct(
        private readonly PlaybackService $playbackService,
        private readonly PlaybackAuthorizationService $authorizationService,
        private readonly BunnyEmbedTokenService $embedTokenService
    ) {}

    public function requestPlayback(
        RequestPlaybackRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User $student */
        $student = $request->user();

        $payload = $this->playbackService->requestPlayback($student, $center, $course, $video);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function updateProgress(
        PlaybackProgressRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User $student */
        $student = $request->user();
        /** @var array{session_id:int,percentage:int} $data */
        $data = $request->validated();

        $session = PlaybackSession::query()
            ->where('id', $data['session_id'])
            ->whereNull('deleted_at')
            ->first() ?? new PlaybackSession;

        $this->authorizationService->assertCanUpdateProgress($student, $center, $course, $video, $session);
        $this->playbackService->updateProgress($student, $session, $data['percentage']);

        return response()->json([
            'success' => true,
        ]);
    }

    public function refreshToken(
        RefreshPlaybackTokenRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User $student */
        $student = $request->user();
        /** @var array{session_id:int} $data */
        $data = $request->validated();

        $session = PlaybackSession::query()
            ->where('id', $data['session_id'])
            ->whereNull('deleted_at')
            ->first() ?? new PlaybackSession;

        $this->authorizationService->assertCanRefreshToken($student, $center, $course, $video, $session);

        $videoUuid = (string) $video->source_id;
        $tokenPayload = $this->embedTokenService->generate($videoUuid, $student, $this->resolveEmbedTokenTtl());

        return response()->json([
            'success' => true,
            'data' => [
                'embed_token' => $tokenPayload['token'],
                'expires_in' => $tokenPayload['expires_in'],
            ],
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
}
