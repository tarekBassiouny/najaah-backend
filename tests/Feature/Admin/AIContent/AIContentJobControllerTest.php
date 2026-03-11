<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Jobs\ProcessAIContentJob;
use App\Models\AIContentJob;
use App\Models\Center;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

it('creates and queues ai content generation job', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'target_type' => AIContentTargetType::Summary->value,
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4o-mini',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center_id', $center->id)
        ->assertJsonPath('data.course_id', $course->id)
        ->assertJsonPath('data.ai_provider', 'openai')
        ->assertJsonPath('data.ai_model', 'gpt-4o-mini');

    $this->assertDatabaseHas('ai_content_jobs', [
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Video->value,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Summary->value,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
    ]);

    Queue::assertPushed(ProcessAIContentJob::class);
});

it('creates ai content job with gemini provider and model', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'target_type' => AIContentTargetType::Summary->value,
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.ai_provider', 'gemini')
        ->assertJsonPath('data.ai_model', 'gemini-1.5-flash');

    $this->assertDatabaseHas('ai_content_jobs', [
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Video->value,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Summary->value,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-1.5-flash',
    ]);

    Queue::assertPushed(ProcessAIContentJob::class);
});

it('returns 422 for invalid ai provider during ai job creation', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'target_type' => AIContentTargetType::Summary->value,
            'ai_provider' => 'invalid-provider',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('returns 422 for source mismatch during ai job creation', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $otherCenter->id]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'target_type' => AIContentTargetType::Summary->value,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

it('reviews approves and publishes ai summary job', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $admin = $this->asCenterAdmin($center);

    $job = AIContentJob::query()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'target_id' => null,
        'status' => AIContentJobStatus::Completed,
        'generation_config' => [],
        'generated_payload' => [
            'title' => 'Generated title',
            'content' => 'Generated content',
        ],
        'created_by' => $admin->id,
        'started_at' => now()->subMinutes(2),
        'completed_at' => now()->subMinute(),
    ]);

    $reviewResponse = $this->patchJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs/{$job->id}/review",
        [
            'reviewed_payload' => [
                'title' => 'Edited title',
                'content' => 'Edited content',
            ],
        ],
        $this->adminHeaders()
    );

    $reviewResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.reviewed_payload.title', 'Edited title');

    $approveResponse = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs/{$job->id}/approve",
        [],
        $this->adminHeaders()
    );

    $approveResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', AIContentJobStatus::Approved->value);

    $publishResponse = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs/{$job->id}/publish",
        [],
        $this->adminHeaders()
    );

    $publishResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.job.status', AIContentJobStatus::Published->value)
        ->assertJsonPath('data.publication.target_type', AIContentTargetType::Summary->value);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Published);
    $this->assertDatabaseHas('learning_assets', [
        'id' => (int) $publishResponse->json('data.publication.target_id'),
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary->value,
        'status' => LearningAssetStatus::Published->value,
        'is_active' => true,
    ]);
});
