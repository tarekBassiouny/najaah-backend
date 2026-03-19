<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Exceptions\RateLimitException;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\Course;
use App\Services\Assessments\AIContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

it('requests json object mode from openai', function (): void {
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
        'language' => 'en',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'title' => 'Lesson Summary',
                        'content' => 'Short summary.',
                    ]),
                ],
            ]],
        ], 200),
    ]);

    app(AIContentService::class)->processJob($job);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request['response_format']['type'] === 'json_object'
            && $request['messages'][0]['role'] === 'system'
            && $request['messages'][1]['role'] === 'user';
    });
});

it('requests json mime type from gemini', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
    ]);

    AIProviderConfig::factory()->create([
        'provider_key' => 'gemini',
        'display_name' => 'Gemini',
        'api_key' => 'test-api-key',
        'default_model' => 'gemini-1.5-flash',
        'models' => ['gemini-1.5-flash'],
    ]);

    $job = AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'language' => 'en',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'gemini',
        'ai_model' => 'gemini-1.5-flash',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'title' => 'Lesson Summary',
                            'content' => 'Short summary.',
                        ]),
                    ]],
                ],
            ]],
        ], 200),
    ]);

    app(AIContentService::class)->processJob($job);

    Http::assertSent(function (Request $request): bool {
        return str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/')
            && $request['generationConfig']['responseMimeType'] === 'application/json';
    });
});

it('requeues pending jobs on provider rate limiting', function (): void {
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
        'language' => 'en',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([], 429),
    ]);

    expect(fn () => app(AIContentService::class)->processJob($job))
        ->toThrow(RateLimitException::class);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Pending)
        ->and($job->error_message)->toBe('OpenAI rate limit exceeded.');
});

it('requeues pending jobs on provider connection timeouts', function (): void {
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
        'language' => 'en',
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake(function (): never {
        throw new ConnectionException('Request timed out');
    });

    expect(fn () => app(AIContentService::class)->processJob($job))
        ->toThrow(ConnectionException::class);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Pending)
        ->and($job->error_message)->toBe('Request timed out');
});
