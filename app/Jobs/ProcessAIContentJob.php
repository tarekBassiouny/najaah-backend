<?php

declare(strict_types=1);

namespace App\Jobs;

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

    public int $backoff = 60;

    public function __construct(
        public readonly AIContentJob $aiContentJob
    ) {
        $this->onQueue((string) config('ai.content_queue', 'ai-content'));
    }

    public function handle(AIContentServiceInterface $service): void
    {
        $service->processJob($this->aiContentJob);
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
