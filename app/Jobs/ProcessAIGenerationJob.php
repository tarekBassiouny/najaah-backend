<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AIGenerationJob;
use App\Services\Assessments\Contracts\AIQuizGeneratorServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAIGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly AIGenerationJob $aiGenerationJob
    ) {}

    public function handle(AIQuizGeneratorServiceInterface $service): void
    {
        $service->processJob($this->aiGenerationJob);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'ai-generation',
            'quiz:' . $this->aiGenerationJob->quiz_id,
            'job:' . $this->aiGenerationJob->id,
        ];
    }
}
