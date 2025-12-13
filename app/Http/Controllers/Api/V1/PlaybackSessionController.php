<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Playback\EndPlaybackSessionRequest;
use App\Http\Requests\Playback\UpdatePlaybackProgressRequest;
use App\Models\User;
use App\Services\Playback\PlaybackSessionService;
use Illuminate\Http\JsonResponse;

class PlaybackSessionController extends Controller
{
    public function __construct(
        private readonly PlaybackSessionService $playbackSessionService
    ) {}

    public function update(UpdatePlaybackProgressRequest $request, int $session): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $updated = $this->playbackSessionService->updateProgress(
            $user,
            $session,
            (int) $request->integer('progress_percent')
        );

        return response()->json([
            'success' => true,
            'message' => 'Playback progress updated',
            'data' => [
                'session_id' => $updated->id,
                'progress_percent' => $updated->progress_percent,
                'is_full_play' => $updated->is_full_play,
                'ended_at' => $updated->ended_at,
            ],
        ]);
    }

    public function end(EndPlaybackSessionRequest $request, int $session): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $progress = $request->input('progress_percent');
        $ended = $this->playbackSessionService->endSession(
            $user,
            $session,
            is_numeric($progress) ? (int) $progress : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Playback session ended',
            'data' => [
                'session_id' => $ended->id,
                'progress_percent' => $ended->progress_percent,
                'is_full_play' => $ended->is_full_play,
                'ended_at' => $ended->ended_at,
            ],
        ]);
    }
}
