<?php

declare(strict_types=1);

namespace App\Services\Playback;

use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConcurrencyService
{
    public function assertNoActiveSession(User $user, UserDevice $device, Video $video): void
    {
        $active = PlaybackSession::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->whereNull('deleted_at')
            ->first();

        if ($active === null) {
            return;
        }

        $this->deny('CONCURRENT_PLAYBACK', 'Another playback session is active.', 403);
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
