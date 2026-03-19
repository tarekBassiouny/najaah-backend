<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use App\Services\Assessments\PromptBuilder;
use Tests\TestCase;

uses(TestCase::class)->group('ai-content', 'services');

it('builds arabic summary prompts with generation config instructions', function (): void {
    $job = AIContentJob::query()->make([
        'center_id' => 1,
        'course_id' => 1,
        'source_type' => AIContentSourceType::Course->value,
        'source_id' => 1,
        'target_type' => AIContentTargetType::Summary->value,
        'language' => 'ar',
        'status' => AIContentJobStatus::Pending->value,
        'generation_config' => [
            'length' => 'long',
            'include_key_points' => true,
        ],
    ]);

    $prompts = app(PromptBuilder::class)->build($job, 'Source content here');

    expect($prompts['system'])->toContain('Return a valid JSON object only.')
        ->and($prompts['user'])->toContain('Use Arabic for every human-readable field.')
        ->toContain('Summary length: long.')
        ->toContain('Include explicit key points or takeaways.')
        ->toContain('"title": "string"')
        ->toContain('"content": "string"');
});

it('builds bilingual quiz prompts with localized output schema', function (): void {
    $job = AIContentJob::query()->make([
        'center_id' => 1,
        'course_id' => 1,
        'source_type' => AIContentSourceType::Course->value,
        'source_id' => 1,
        'target_type' => AIContentTargetType::Quiz->value,
        'language' => 'both',
        'status' => AIContentJobStatus::Pending->value,
        'generation_config' => [
            'question_count' => 6,
            'difficulty' => 'hard',
            'question_styles' => ['multiple_choice', 'true_false'],
        ],
    ]);

    $prompts = app(PromptBuilder::class)->build($job, 'Course source text');

    expect($prompts['user'])->toContain('return an object with exactly `ar` and `en` keys')
        ->toContain('Question count: 6.')
        ->toContain('Target difficulty: hard.')
        ->toContain('Allowed question styles: multiple_choice, true_false.')
        ->toContain('"question": {')
        ->toContain('"ar": "string"')
        ->toContain('"en": "string"');
});

it('builds retry prompts with validation feedback', function (): void {
    $job = AIContentJob::query()->make([
        'center_id' => 1,
        'course_id' => 1,
        'source_type' => AIContentSourceType::Course->value,
        'source_id' => 1,
        'target_type' => AIContentTargetType::Summary->value,
        'language' => 'en',
        'status' => AIContentJobStatus::Pending->value,
        'generation_config' => [],
    ]);

    $prompts = app(PromptBuilder::class)->buildRetryPrompt($job, 'Source content', [
        'title is required',
        'content must be shorter',
    ]);

    expect($prompts['user'])->toContain('PREVIOUS OUTPUT ISSUES:')
        ->toContain('- title is required')
        ->toContain('- content must be shorter')
        ->toContain('Return a corrected JSON object that fixes every issue above.');
});
