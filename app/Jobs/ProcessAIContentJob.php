<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AIContentJobStatus;
use App\Models\AIContentJob;
use App\Services\Assessments\Contracts\AIContentServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAIContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int> */
    public array $backoff = [30, 90, 270];

    public function __construct(
        public readonly AIContentJob $aiContentJob
    ) {
        $this->onQueue((string) config('ai.content_queue', 'ai-content'));
    }

    public function handle(AIContentServiceInterface $service): void
    {
        $job = AIContentJob::query()->find($this->aiContentJob->id);
        if (! $job instanceof AIContentJob) {
            return;
        }

        if (! in_array($job->status, [AIContentJobStatus::Pending, AIContentJobStatus::Failed], true)) {
            return;
        }

        $service->processJob($job);
    }

    public function failed(\Throwable $exception): void
    {
        $job = AIContentJob::query()->find($this->aiContentJob->id);
        if (! $job instanceof AIContentJob) {
            return;
        }

        if (! in_array($job->status, [AIContentJobStatus::Pending, AIContentJobStatus::Processing, AIContentJobStatus::Failed], true)) {
            return;
        }

        $job->update([
            'status' => AIContentJobStatus::Failed,
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }

    /**
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'ai-content',
            'center:'.$this->aiContentJob->center_id,
            'course:'.$this->aiContentJob->course_id,
            'job:'.$this->aiContentJob->id,
        ];
    }
}
