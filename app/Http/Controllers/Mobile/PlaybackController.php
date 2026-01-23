<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\CloseSessionRequest;
use App\Http\Requests\Mobile\PlaybackProgressRequest;
use App\Http\Requests\Mobile\RefreshPlaybackTokenRequest;
use App\Http\Requests\Mobile\RequestPlaybackRequest;
use App\Http\Resources\Mobile\PlaybackSessionResource;
use App\Http\Resources\Mobile\PlaybackTokenResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\PlaybackAuthorizationService;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;

class PlaybackController extends Controller
{
    public function __construct(
        private readonly PlaybackService $playbackService,
        private readonly PlaybackAuthorizationService $authorizationService
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
            'data' => new PlaybackSessionResource($payload),
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
        $progress = $this->playbackService->updateProgress($student, $session, $data['percentage']);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    public function closeSession(
        CloseSessionRequest $request,
        Center $center,
        Course $course,
        Video $video
    ): JsonResponse {
        $session = PlaybackSession::find($request->integer('session_id'));

        if (! $session instanceof PlaybackSession || $session->video_id !== $video->id || $session->course_id !== $course->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SESSION_MISMATCH',
                    'message' => 'Session does not belong to this video/course.',
                ],
            ], 403);
        }

        $this->playbackService->closeSession(
            sessionId: $session->id,
            watchDuration: $request->integer('watch_duration'),
            reason: 'user'
        );

        return response()->json(['success' => true]);
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

        $payload = $this->playbackService->refreshEmbedToken($student, $center, $course, $video, $session);

        return response()->json([
            'success' => true,
            'data' => new PlaybackTokenResource($payload),
        ]);
    }
}
