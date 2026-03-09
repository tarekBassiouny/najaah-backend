<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Auto-submit timed out quiz attempts.
 *
 * This job should be scheduled to run every minute to check for
 * any in-progress attempts that have exceeded their time limit.
 */
class AutoSubmitTimedOutAttempts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    public function handle(QuizAttemptServiceInterface $attemptService): void
    {
        $count = $attemptService->autoSubmitTimedOut();

        if ($count > 0) {
            Log::info('Auto-submitted timed out quiz attempts', [
                'count' => $count,
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['quiz', 'auto-submit'];
    }
}
