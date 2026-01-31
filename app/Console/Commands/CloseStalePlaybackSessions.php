<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlaybackSession;
use App\Services\Playback\PlaybackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseStalePlaybackSessions extends Command
{
    protected $signature = 'playback:close-stale';

    protected $description = 'Close playback sessions that have expired.';

    public function handle(PlaybackService $playbackService): int
    {
        $staleSessions = PlaybackSession::query()
            ->whereNull('ended_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($staleSessions as $session) {
            $playbackService->closeSession(
                sessionId: $session->id,
                watchDuration: $session->watch_duration ?? 0,
                reason: 'timeout'
            );
            $count++;
        }

        Log::channel('domain')->info('playback_sessions_cleanup', [
            'closed_sessions' => $count,
            'mode' => 'expires_at',
        ]);

        $this->info(sprintf('Closed %d stale sessions.', $count));

        return Command::SUCCESS;
    }
}
