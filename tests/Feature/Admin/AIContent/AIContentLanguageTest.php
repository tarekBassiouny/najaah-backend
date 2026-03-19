<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Quiz;
use App\Services\Assessments\AIContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

it('processes openai jobs with separated system and user prompts', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asCenterAdmin($center);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'created_by' => $admin->id,
        'title_translations' => ['en' => 'Algebra Course', 'ar' => 'دورة الجبر'],
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
        'language' => 'both',
        'status' => AIContentJobStatus::Pending,
        'generation_config' => [
            'length' => 'long',
            'include_key_points' => true,
        ],
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'title' => ['ar' => 'ملخص الجبر', 'en' => 'Algebra Summary'],
                        'content' => ['ar' => 'محتوى عربي', 'en' => 'English content'],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]],
        ], 200),
    ]);

    app(AIContentService::class)->processJob($job);

    $job->refresh();

    expect($job->status)->toBe(AIContentJobStatus::Completed)
        ->and(data_get($job->generated_payload, 'title.ar'))->toBe('ملخص الجبر')
        ->and($job->prompt_used)->toBeString();

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request['messages'][0]['role'] === 'system'
            && $request['messages'][1]['role'] === 'user'
            && str_contains((string) $request['messages'][1]['content'], 'Use Arabic for every human-readable field.') === false
            && str_contains((string) $request['messages'][1]['content'], 'return an object with exactly `ar` and `en` keys')
            && str_contains((string) $request['messages'][1]['content'], 'Summary length: long.')
            && str_contains((string) $request['messages'][1]['content'], 'Include explicit key points or takeaways.');
    });
});

it('publishes arabic summary strings into arabic translations', function (): void {
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
        'language' => 'ar',
        'status' => AIContentJobStatus::Approved,
        'generated_payload' => [
            'title' => 'ملخص الدرس',
            'content' => 'هذا ملخص عربي.',
        ],
        'created_by' => $admin->id,
        'reviewed_by' => $admin->id,
    ]);

    $result = app(AIContentService::class)->publishJob($job, $admin);
    $asset = LearningAsset::query()->findOrFail((int) $result['target_id']);

    expect($asset->title_translations)->toBe(['ar' => 'ملخص الدرس'])
        ->and($asset->content_translations)->toBe(['ar' => 'هذا ملخص عربي.']);
});

it('preserves bilingual summary payloads when publishing', function (): void {
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
        'status' => AIContentJobStatus::Approved,
        'generated_payload' => [
            'title' => ['ar' => 'ملخص', 'en' => 'Summary'],
            'content' => ['ar' => 'محتوى', 'en' => 'Content'],
        ],
        'created_by' => $admin->id,
        'reviewed_by' => $admin->id,
    ]);

    $result = app(AIContentService::class)->publishJob($job, $admin);
    $asset = LearningAsset::query()->findOrFail((int) $result['target_id']);

    expect($asset->title_translations)->toBe([
        'ar' => 'ملخص',
        'en' => 'Summary',
    ])->and($asset->content_translations)->toBe([
        'ar' => 'محتوى',
        'en' => 'Content',
    ]);
});

it('publishes bilingual quiz questions and answers without dropping localized text nodes', function (): void {
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
        'target_type' => AIContentTargetType::Quiz,
        'language' => 'both',
        'status' => AIContentJobStatus::Approved,
        'generated_payload' => [
            'quiz' => [
                'title' => ['ar' => 'اختبار', 'en' => 'Quiz'],
                'description' => ['ar' => 'وصف', 'en' => 'Description'],
            ],
            'questions' => [[
                'question' => ['ar' => 'ما الناتج؟', 'en' => 'What is the result?'],
                'explanation' => ['ar' => 'اشرح', 'en' => 'Explain'],
                'points' => 2,
                'options' => [
                    ['text' => ['ar' => '٢', 'en' => '2'], 'is_correct' => true],
                    ['text' => ['ar' => '٣', 'en' => '3'], 'is_correct' => false],
                ],
            ]],
        ],
        'created_by' => $admin->id,
        'reviewed_by' => $admin->id,
    ]);

    $result = app(AIContentService::class)->publishJob($job, $admin);

    $quiz = Quiz::query()->findOrFail((int) $result['target_id']);
    $question = $quiz->questions()->with('answers')->firstOrFail();

    expect($result['questions_added'])->toBe(1)
        ->and($question->question_translations)->toBe([
            'ar' => 'ما الناتج؟',
            'en' => 'What is the result?',
        ])
        ->and($question->answers)->toHaveCount(2)
        ->and($question->answers->pluck('answer_translations')->values()->all())->toBe([
            ['ar' => '٢', 'en' => '2'],
            ['ar' => '٣', 'en' => '3'],
        ]);
});
