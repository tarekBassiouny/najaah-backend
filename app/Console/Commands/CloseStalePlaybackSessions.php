<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlaybackSession;
use App\Services\Playback\PlaybackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseStalePlaybackSessions extends Command
{
    protected $signature = 'playback:close-stale {--timeout= : Timeout in seconds}';

    protected $description = 'Close playback sessions with no activity for specified seconds.';

    public function handle(PlaybackService $playbackService): int
    {
        $timeout = (int) ($this->option('timeout') ?? config('playback.session_timeout_seconds'));

        $staleSessions = PlaybackSession::query()
            ->whereNull('ended_at')
            ->where('last_activity_at', '<', now()->subSeconds($timeout))
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
            'timeout_seconds' => $timeout,
        ]);

        $this->info(sprintf('Closed %d stale sessions.', $count));

        return Command::SUCCESS;
    }
}
