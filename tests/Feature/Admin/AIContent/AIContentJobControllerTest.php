<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\CenterType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Enums\TextExtractionStatus;
use App\Jobs\ProcessAIContentJob;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

function configureAiProvider(string $provider = 'openai', ?string $model = null): void
{
    $resolvedModel = $model ?? match ($provider) {
        'gemini' => 'gemini-1.5-flash',
        default => 'gpt-4o-mini',
    };

    AIProviderConfig::factory()->create([
        'provider_key' => $provider,
        'display_name' => ucfirst($provider),
        'default_model' => $resolvedModel,
        'models' => [$resolvedModel],
        'api_key' => 'test-api-key',
    ]);
}

it('creates and queues ai content generation job', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => 'Lesson transcript',
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider();

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
        ->assertJsonPath('data.language', 'ar')
        ->assertJsonPath('data.ai_provider', 'openai')
        ->assertJsonPath('data.ai_model', 'gpt-4o-mini');

    $this->assertDatabaseHas('ai_content_jobs', [
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Video->value,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Summary->value,
        'language' => 'ar',
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
    ]);

    Queue::assertPushed(ProcessAIContentJob::class);
});

it('creates ai content job with gemini provider and model', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => 'Lesson transcript',
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider('gemini', 'gemini-1.5-flash');

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
        ->assertJsonPath('data.language', 'ar')
        ->assertJsonPath('data.ai_provider', 'gemini')
        ->assertJsonPath('data.ai_model', 'gemini-1.5-flash');

    $this->assertDatabaseHas('ai_content_jobs', [
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Video->value,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Summary->value,
        'language' => 'ar',
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

it('returns 422 when generating from a video source without transcript', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => null,
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider();

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
        ->assertJsonPath('error.code', 'TRANSCRIPT_NOT_FOUND');
});

it('returns 422 when generating from a pdf source before extraction completes', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $pdf = Pdf::factory()->create([
        'center_id' => $center->id,
        'text_content' => null,
        'text_extraction_status' => TextExtractionStatus::Pending,
    ]);
    $course->pdfs()->attach($pdf->id, [
        'section_id' => null,
        'video_id' => null,
        'order_index' => 1,
        'visible' => true,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider();

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Pdf->value,
            'source_id' => $pdf->id,
            'target_type' => AIContentTargetType::Summary->value,
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'PDF_NOT_READY');
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

it('creates and queues ai content generation batch', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => 'Lesson transcript',
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider();

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/batches",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'assets' => [
                [
                    'target_type' => AIContentTargetType::Summary->value,
                    'ai_provider' => 'openai',
                    'ai_model' => 'gpt-4o-mini',
                    'generation_config' => [
                        'length' => 'medium',
                    ],
                ],
                [
                    'target_type' => AIContentTargetType::Quiz->value,
                    'ai_provider' => 'openai',
                    'ai_model' => 'gpt-4o-mini',
                    'generation_config' => [
                        'question_count' => 5,
                        'question_styles' => ['single_choice'],
                    ],
                ],
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.jobs.0.language', 'ar')
        ->assertJsonCount(2, 'data.jobs');

    $batchKey = $response->json('data.batch_key');

    expect($batchKey)->not->toBeNull();

    $jobs = AIContentJob::query()
        ->where('batch_key', $batchKey)
        ->orderBy('id')
        ->get();

    expect($jobs)->toHaveCount(2);
    expect($jobs->pluck('target_type')->map->value->all())->toBe([
        AIContentTargetType::Summary->value,
        AIContentTargetType::Quiz->value,
    ]);

    Queue::assertPushed(ProcessAIContentJob::class, 2);
});

it('defaults language to arabic for single jobs and persists it', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => 'Lesson transcript',
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $this->asCenterAdmin($center);
    configureAiProvider();

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

    $response->assertStatus(202)
        ->assertJsonPath('data.language', 'ar');

    $this->assertDatabaseHas('ai_content_jobs', [
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Video->value,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Summary->value,
        'language' => 'ar',
    ]);
});

it('validates language on single jobs', function (): void {
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
            'language' => 'fr',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('applies shared generation config validation to single jobs', function (): void {
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
            'target_type' => AIContentTargetType::Quiz->value,
            'generation_config' => [
                'question_count' => 50,
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('supports section source and interactive activity in batch jobs', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $section = Section::factory()->create(['course_id' => $course->id]);

    $this->asCenterAdmin($center);
    configureAiProvider();

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/batches",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Section->value,
            'source_id' => $section->id,
            'language' => 'both',
            'assets' => [
                [
                    'target_type' => AIContentTargetType::InteractiveActivity->value,
                ],
                [
                    'target_type' => AIContentTargetType::Summary->value,
                ],
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.jobs.0.language', 'both')
        ->assertJsonCount(2, 'data.jobs');

    $batchKey = $response->json('data.batch_key');

    $jobs = AIContentJob::query()
        ->where('batch_key', $batchKey)
        ->orderBy('id')
        ->get();

    expect($jobs)->toHaveCount(2);
    expect($jobs->pluck('source_type')->map->value->unique()->all())->toBe([
        AIContentSourceType::Section->value,
    ]);
    expect($jobs->pluck('target_type')->map->value->all())->toBe([
        AIContentTargetType::InteractiveActivity->value,
        AIContentTargetType::Summary->value,
    ]);
    expect($jobs->pluck('language')->unique()->all())->toBe(['both']);

    Queue::assertPushed(ProcessAIContentJob::class, 2);
});

it('supports course source in batch jobs', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    configureAiProvider();

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/batches",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Course->value,
            'source_id' => $course->id,
            'assets' => [
                [
                    'target_type' => AIContentTargetType::Summary->value,
                ],
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(202)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.jobs.0.source_type', AIContentSourceType::Course->value)
        ->assertJsonPath('data.jobs.0.language', 'ar');

    Queue::assertPushed(ProcessAIContentJob::class);
});

it('filters ai content jobs by batch key', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $admin = $this->asCenterAdmin($center);

    $matchingBatchKey = (string) \Illuminate\Support\Str::uuid();

    AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'batch_key' => $matchingBatchKey,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'created_by' => $admin->id,
    ]);

    AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'batch_key' => (string) \Illuminate\Support\Str::uuid(),
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Quiz,
        'created_by' => $admin->id,
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs?batch_key={$matchingBatchKey}",
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.batch_key', $matchingBatchKey);
});

it('rejects batch target when it belongs to a different source item', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $videoA = Video::factory()->create(['center_id' => $center->id]);
    $videoB = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($videoA->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);
    $course->videos()->attach($videoB->id, [
        'section_id' => null,
        'order_index' => 2,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->create([
        'center_id' => $center->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $videoB->id,
    ]);

    $this->asCenterAdmin($center);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/batches",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $videoA->id,
            'assets' => [
                [
                    'target_type' => AIContentTargetType::Quiz->value,
                    'target_id' => $quiz->id,
                ],
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

it('publishes ai quiz into a new draft version when target quiz is live', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $admin = $this->asCenterAdmin($center);

    $liveQuiz = Quiz::factory()->forCourse($course)->active()->create([
        'center_id' => $center->id,
        'attachable_type' => AIContentSourceType::Video->value,
        'attachable_id' => $video->id,
        'title_translations' => ['en' => 'Live quiz'],
    ]);
    $oldQuestion = QuizQuestion::factory()->create([
        'quiz_id' => $liveQuiz->id,
        'question_translations' => ['en' => 'Old question?'],
    ]);
    QuizAnswer::factory()->correct()->create([
        'quiz_question_id' => $oldQuestion->id,
        'answer_translations' => ['en' => 'Old answer'],
    ]);

    $job = AIContentJob::query()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'batch_key' => null,
        'source_type' => AIContentSourceType::Video,
        'source_id' => $video->id,
        'target_type' => AIContentTargetType::Quiz,
        'target_id' => $liveQuiz->id,
        'language' => 'en',
        'status' => AIContentJobStatus::Approved,
        'generation_config' => [],
        'reviewed_payload' => [
            'quiz' => [
                'title' => 'New quiz title',
                'description' => 'New quiz description',
            ],
            'questions' => [
                [
                    'question' => 'New generated question?',
                    'options' => [
                        ['text' => 'Correct answer', 'is_correct' => true],
                        ['text' => 'Wrong answer', 'is_correct' => false],
                    ],
                    'explanation' => 'Because it is correct.',
                    'points' => 2,
                ],
            ],
        ],
        'created_by' => $admin->id,
        'reviewed_by' => $admin->id,
        'started_at' => now()->subMinutes(2),
        'completed_at' => now()->subMinute(),
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs/{$job->id}/publish",
        [],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.publication.target_type', AIContentTargetType::Quiz->value);

    $newQuizId = (int) $response->json('data.publication.target_id');

    expect($newQuizId)->not->toBe($liveQuiz->id);

    $liveQuiz->refresh();
    $newQuiz = Quiz::query()->findOrFail($newQuizId);

    expect($liveQuiz->is_active)->toBeFalse();
    expect($liveQuiz->translate('title'))->toBe('Live quiz');
    expect($liveQuiz->questions()->count())->toBe(1);

    expect($newQuiz->is_active)->toBeTrue();
    expect($newQuiz->attachable_type)->toBe(AIContentSourceType::Video->value);
    expect($newQuiz->attachable_id)->toBe($video->id);
    expect($newQuiz->translate('title'))->toBe('New quiz title');
    expect($newQuiz->questions()->count())->toBe(1);
    expect($newQuiz->questions()->first()?->translate('question'))->toBe('New generated question?');
});
