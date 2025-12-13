<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

class PlaybackSessionService
{
    public function startSession(User $user, Video $video, UserDevice $device): PlaybackSession
    {
        $this->forceEndActiveSession($user);

        return PlaybackSession::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'device_id' => $device->id,
            'started_at' => Carbon::now(),
            'progress_percent' => 0,
            'is_full_play' => false,
        ]);
    }

    public function updateProgress(User $user, int $sessionId, int $progressPercent): PlaybackSession
    {
        $session = $this->findUserSession($user, $sessionId);

        if ($session->ended_at !== null) {
            $this->deny('SESSION_ENDED', 'Playback session already ended.', 409);
        }

        if ($progressPercent < $session->progress_percent) {
            return $session;
        }

        $session->progress_percent = $progressPercent;
        $this->applyFullPlayDetection($session, $user);
        $session->save();

        return $session->fresh() ?? $session;
    }

    public function endSession(User $user, int $sessionId, ?int $progressPercent = null): PlaybackSession
    {
        $session = $this->findUserSession($user, $sessionId);

        if ($session->ended_at !== null) {
            return $session;
        }

        if ($progressPercent !== null && $progressPercent >= $session->progress_percent) {
            $session->progress_percent = $progressPercent;
        }

        $this->applyFullPlayDetection($session, $user);
        $session->ended_at = Carbon::now();
        $session->save();

        return $session->fresh() ?? $session;
    }

    private function findUserSession(User $user, int $sessionId): PlaybackSession
    {
        $session = PlaybackSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if ($session === null) {
            $this->deny('SESSION_NOT_FOUND', 'Playback session not found.', 404);
        }

        return $session;
    }

    private function forceEndActiveSession(User $user): void
    {
        PlaybackSession::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => Carbon::now()]);
    }

    private function applyFullPlayDetection(PlaybackSession $session, User $user): void
    {
        if ($session->is_full_play || $session->progress_percent < 95) {
            return;
        }

        $video = $session->video()->with('courses')->first();

        if ($video === null) {
            return;
        }

        $courseIds = $video->courses->pluck('id')->all();

        if (empty($courseIds)) {
            return;
        }

        $hasActiveEnrollment = Enrollment::where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveEnrollment) {
            $session->is_full_play = true;
        }
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
