<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\Course;
use App\Services\Assessments\AIContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

it('retries once when the first ai payload is invalid and stores validation warnings', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'api_key' => 'test-api-key',
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
    ]);

    $job = AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'language' => 'ar',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::sequence()
            ->push([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title' => 'عنوان فقط',
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200)
            ->push([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title' => 'ملخص الدرس',
                            'content' => 'هذا ملخص عربي.',
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
    ]);

    app(AIContentService::class)->processJob($job);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Completed)
        ->and($job->validation_warnings)->toContain('content must be a non-empty text field.')
        ->and($job->generated_payload)->toBe([
            'title' => 'ملخص الدرس',
            'content' => 'هذا ملخص عربي.',
        ])
        ->and($job->prompt_used)->toContain('"retry"');
});

it('fails the job when ai output is still invalid after retry and stores validation warnings', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'api_key' => 'test-api-key',
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
    ]);

    $job = AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'language' => 'ar',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::sequence()
            ->push([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['title' => 'Bad'], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200)
            ->push([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['title' => 'Still bad'], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
    ]);

    app(AIContentService::class)->processJob($job);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Failed)
        ->and($job->validation_warnings)->toContain('content must be a non-empty text field.')
        ->and($job->error_message)->toBe('AI output validation failed after retry.');
});

it('rejects invalid reviewed payloads before approval flow continues', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    $job = AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'language' => 'both',
        'status' => AIContentJobStatus::Completed,
        'generated_payload' => [
            'title' => ['ar' => 'ملخص', 'en' => 'Summary'],
            'content' => ['ar' => 'محتوى', 'en' => 'Content'],
        ],
        'created_by' => $admin->id,
    ]);

    expect(fn () => app(AIContentService::class)->reviewJob($job, [
        'title' => 'Collapsed title',
        'content' => 'Collapsed content',
    ], $admin))->toThrow(\App\Exceptions\DomainException::class);
});
