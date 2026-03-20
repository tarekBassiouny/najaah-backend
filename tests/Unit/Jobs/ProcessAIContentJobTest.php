<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Jobs\ProcessAIContentJob;
use App\Models\AIContentJob;
use App\Services\Assessments\Contracts\AIContentServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('ai-content', 'jobs');

it('uses phased queue backoff values', function (): void {
    $aiContentJob = AIContentJob::factory()->create();

    $job = new ProcessAIContentJob($aiContentJob);

    expect($job->backoff)->toBe([30, 90, 270]);
});

it('marks the ai content job as failed when retries are exhausted', function (): void {
    $aiContentJob = AIContentJob::factory()->create([
        'status' => AIContentJobStatus::Pending,
        'error_message' => null,
        'completed_at' => null,
    ]);

    $job = new ProcessAIContentJob($aiContentJob);
    $job->failed(new RuntimeException('Provider remained unavailable.'));

    $aiContentJob->refresh();

    expect($aiContentJob->status)->toBe(AIContentJobStatus::Failed)
        ->and($aiContentJob->error_message)->toBe('Provider remained unavailable.')
        ->and($aiContentJob->completed_at)->not()->toBeNull();
});

it('skips queued processing when the ai content job is no longer processable', function (): void {
    $aiContentJob = AIContentJob::factory()->create([
        'status' => AIContentJobStatus::Completed,
    ]);

    $service = \Mockery::mock(AIContentServiceInterface::class);
    $service->shouldNotReceive('processJob');

    $job = new ProcessAIContentJob($aiContentJob);
    $job->handle($service);
});

it('does not overwrite completed ai content jobs in the failed hook', function (): void {
    $aiContentJob = AIContentJob::factory()->create([
        'status' => AIContentJobStatus::Completed,
        'error_message' => null,
        'completed_at' => now()->subMinute(),
    ]);

    $completedAt = $aiContentJob->completed_at;

    $job = new ProcessAIContentJob($aiContentJob);
    $job->failed(new RuntimeException('Late duplicate queue execution.'));

    $aiContentJob->refresh();

    expect($aiContentJob->status)->toBe(AIContentJobStatus::Completed)
        ->and($aiContentJob->error_message)->toBeNull()
        ->and($aiContentJob->completed_at?->equalTo($completedAt))->toBeTrue();
});

it('keeps queue retry_after above the ai content job timeout', function (): void {
    expect((int) config('queue.connections.database.retry_after'))->toBeGreaterThan(300)
        ->and((int) config('queue.connections.redis.retry_after'))->toBeGreaterThan(300)
        ->and((int) config('queue.connections.beanstalkd.retry_after'))->toBeGreaterThan(300);
});
