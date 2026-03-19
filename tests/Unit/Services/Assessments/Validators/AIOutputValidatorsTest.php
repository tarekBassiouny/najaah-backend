<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use App\Services\Assessments\Validators\AssignmentOutputValidator;
use App\Services\Assessments\Validators\FlashcardsOutputValidator;
use App\Services\Assessments\Validators\InteractiveActivityOutputValidator;
use App\Services\Assessments\Validators\QuizOutputValidator;
use App\Services\Assessments\Validators\SummaryOutputValidator;
use Tests\TestCase;

uses(TestCase::class)->group('ai-content', 'validators');

function makeValidationJob(string $targetType, string $language = 'ar'): AIContentJob
{
    return AIContentJob::query()->make([
        'center_id' => 1,
        'course_id' => 1,
        'source_type' => AIContentSourceType::Course->value,
        'source_id' => 1,
        'target_type' => $targetType,
        'language' => $language,
        'status' => AIContentJobStatus::Pending->value,
        'generation_config' => [],
    ]);
}

it('validates summary output for bilingual jobs', function (): void {
    $validator = app(SummaryOutputValidator::class);

    $valid = $validator->validate(makeValidationJob(AIContentTargetType::Summary->value, 'both'), [
        'title' => ['ar' => 'ملخص', 'en' => 'Summary'],
        'content' => ['ar' => 'محتوى', 'en' => 'Content'],
    ]);

    $invalid = $validator->validate(makeValidationJob(AIContentTargetType::Summary->value, 'both'), [
        'title' => 'Summary',
        'content' => ['ar' => 'محتوى'],
    ]);

    expect($valid)->toBe([])
        ->and($invalid)->not()->toBe([])
        ->and($invalid[0])->toContain('title');
});

it('validates flashcards output shape', function (): void {
    $validator = app(FlashcardsOutputValidator::class);

    $errors = $validator->validate(makeValidationJob(AIContentTargetType::Flashcards->value), [
        'title' => 'Cards',
        'cards' => [
            ['front' => 'A', 'back' => 'B'],
            ['front' => '', 'back' => 'C'],
        ],
    ]);

    expect($errors)->toContain('cards.1.front must be a non-empty text field.');
});

it('validates interactive activity output shape', function (): void {
    $validator = app(InteractiveActivityOutputValidator::class);

    $errors = $validator->validate(makeValidationJob(AIContentTargetType::InteractiveActivity->value), [
        'title' => 'Activity',
        'instructions' => 'Do the steps',
        'steps' => [
            ['title' => 'Step 1', 'description' => 'Desc', 'estimated_seconds' => 60],
            ['title' => 'Step 2', 'description' => 'Desc', 'estimated_seconds' => 0],
        ],
    ]);

    expect($errors)->toContain('steps.1.estimated_seconds must be a positive number.');
});

it('validates assignment output shape', function (): void {
    $validator = app(AssignmentOutputValidator::class);

    $errors = $validator->validate(makeValidationJob(AIContentTargetType::Assignment->value), [
        'assignment' => [
            'title' => 'Assignment',
            'description' => 'Solve the questions',
            'submission_types' => [0, 4],
            'max_points' => 20,
            'passing_score' => 25,
        ],
    ]);

    expect($errors)->toContain('assignment.submission_types.1 must be one of 0, 1, or 2.')
        ->and($errors)->toContain('assignment.passing_score must not exceed assignment.max_points.');
});

it('validates quiz output shape', function (): void {
    $validator = app(QuizOutputValidator::class);

    $errors = $validator->validate(makeValidationJob(AIContentTargetType::Quiz->value), [
        'quiz' => [
            'title' => 'Quiz',
            'description' => 'Desc',
        ],
        'questions' => [[
            'question' => 'What is x?',
            'options' => [
                ['text' => 'Answer A', 'is_correct' => false],
                ['text' => 'Answer B', 'is_correct' => false],
            ],
            'points' => 1,
        ]],
    ]);

    expect($errors)->toContain('questions.0 must include at least one correct option.');
});
