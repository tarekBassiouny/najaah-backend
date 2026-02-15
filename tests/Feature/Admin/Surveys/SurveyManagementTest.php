<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\SurveyAssignableType;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScopeType;
use App\Enums\SurveyType;
use App\Models\Center;
use App\Models\Course;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('surveys', 'admin');

it('lists surveys for super admin', function (): void {
    $this->asAdmin();
    Survey::factory()->system()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/surveys', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'scope_type', 'title', 'type', 'is_active'],
            ],
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ]);
});

it('super admin sees system surveys on system endpoint', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    Survey::factory()->system()->count(2)->create();
    Survey::factory()->center($center)->count(3)->create();

    // System endpoint returns only system surveys
    $response = $this->getJson('/api/v1/admin/surveys', $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('super admin can filter surveys by scope type', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    Survey::factory()->system()->count(2)->create();
    Survey::factory()->center($center)->count(3)->create();

    $response = $this->getJson('/api/v1/admin/surveys?scope_type='.SurveyScopeType::System->value, $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    foreach ($response->json('data') as $survey) {
        expect($survey['scope_type'])->toBe(SurveyScopeType::System->value);
    }
});

it('creates a system survey with questions', function (): void {
    $admin = $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Feedback Survey', 'ar' => 'استطلاع ردود الفعل'],
        'description_translations' => ['en' => 'Please provide feedback', 'ar' => 'يرجى تقديم ملاحظاتك'],
        'type' => SurveyType::Feedback->value,
        'is_active' => true,
        'is_mandatory' => false,
        'allow_multiple_submissions' => false,
        'questions' => [
            [
                'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                'type' => SurveyQuestionType::Rating->value,
                'is_required' => true,
                'order_index' => 0,
            ],
            [
                'question_translations' => ['en' => 'Any comments?', 'ar' => 'أي تعليقات؟'],
                'type' => SurveyQuestionType::Text->value,
                'is_required' => false,
                'order_index' => 1,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.scope_type', SurveyScopeType::System->value)
        ->assertJsonPath('data.title_translations.en', 'Feedback Survey');

    $this->assertDatabaseHas('surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'center_id' => null,
        'created_by' => $admin->id,
    ]);

    expect(SurveyQuestion::where('survey_id', $response->json('data.id'))->count())->toBe(2);
});

it('creates a system survey with all assignment and resolves assignment names', function (): void {
    $this->asAdmin();

    $eligibleStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'name' => 'Eligible Student',
    ]);

    $brandedCenter = Center::factory()->create(['type' => CenterType::Branded]);
    User::factory()->create([
        'is_student' => true,
        'center_id' => $brandedCenter->id,
    ]);

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'All Assignment Survey', 'ar' => 'استبيان الكل'],
        'type' => SurveyType::Feedback->value,
        'is_active' => true,
        'assignments' => [
            ['type' => SurveyAssignableType::All->value],
        ],
        'questions' => [
            [
                'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                'type' => SurveyQuestionType::Rating->value,
                'is_required' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.assignments')
        ->assertJsonPath('data.assignments.0.type', SurveyAssignableType::All->value)
        ->assertJsonPath('data.assignments.0.assignable_id', $eligibleStudent->id)
        ->assertJsonPath('data.assignments.0.assignable_name', $eligibleStudent->name);
});

it('creates a center survey when assignment id is a numeric string', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys", [
        'title_translations' => ['en' => 'String ID Assignment', 'ar' => 'تعيين بمعرف نصي'],
        'type' => SurveyType::Feedback->value,
        'is_active' => true,
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => (string) $course->id],
        ],
        'questions' => [
            [
                'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                'type' => SurveyQuestionType::Rating->value,
                'is_required' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.assignments.0.type', SurveyAssignableType::Course->value)
        ->assertJsonPath('data.assignments.0.assignable_id', $course->id);

    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $response->json('data.id'),
        'assignable_type' => SurveyAssignableType::Course->value,
        'assignable_id' => $course->id,
    ]);
});

it('creates a survey with far future schedule dates', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Future Survey', 'ar' => 'استبيان مستقبلي'],
        'description_translations' => ['en' => 'Long-term survey window', 'ar' => 'نافذة طويلة للاستبيان'],
        'type' => SurveyType::Mandatory->value,
        'is_active' => false,
        'is_mandatory' => true,
        'allow_multiple_submissions' => true,
        'start_at' => '2026-02-10',
        'end_at' => '2052-03-05',
        'questions' => [
            [
                'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                'type' => SurveyQuestionType::Rating->value,
                'is_required' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.end_at', '2052-03-05');
});

it('creates survey with single choice question and options', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Poll', 'ar' => 'استطلاع'],
        'type' => SurveyType::Poll->value,
        'questions' => [
            [
                'question_translations' => ['en' => 'Favorite color?', 'ar' => 'اللون المفضل؟'],
                'type' => SurveyQuestionType::SingleChoice->value,
                'is_required' => true,
                'options' => [
                    ['option_translations' => ['en' => 'Red', 'ar' => 'أحمر'], 'order_index' => 0],
                    ['option_translations' => ['en' => 'Blue', 'ar' => 'أزرق'], 'order_index' => 1],
                    ['option_translations' => ['en' => 'Green', 'ar' => 'أخضر'], 'order_index' => 2],
                ],
            ],
        ],
    ], $this->adminHeaders());

    $response->assertCreated();
    $surveyId = $response->json('data.id');
    $question = SurveyQuestion::where('survey_id', $surveyId)->first();
    expect($question->options()->count())->toBe(3);
});

it('rejects datetime values for schedule fields', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Date format check', 'ar' => 'تحقق من التاريخ'],
        'type' => SurveyType::Feedback->value,
        'start_at' => '2026-02-10T00:00:00Z',
        'questions' => [
            [
                'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                'type' => SurveyQuestionType::Rating->value,
                'is_required' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('requires options for single choice questions', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Poll', 'ar' => 'استطلاع'],
        'type' => SurveyType::Poll->value,
        'questions' => [
            [
                'question_translations' => ['en' => 'Favorite?', 'ar' => 'المفضل؟'],
                'type' => SurveyQuestionType::SingleChoice->value,
                'is_required' => true,
                // No options provided
            ],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('rejects creating survey with more than 10 questions', function (): void {
    $this->asAdmin();

    $questions = array_map(
        static fn (int $i): array => [
            'question_translations' => [
                'en' => "Question $i",
                'ar' => "سؤال $i",
            ],
            'type' => SurveyQuestionType::Text->value,
            'is_required' => true,
            'order_index' => $i,
        ],
        range(1, 11)
    );

    $response = $this->postJson('/api/v1/admin/surveys', [
        'scope_type' => SurveyScopeType::System->value,
        'title_translations' => ['en' => 'Too many questions', 'ar' => 'أسئلة كثيرة'],
        'type' => SurveyType::Feedback->value,
        'questions' => $questions,
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.details.questions'))->not->toBeEmpty();
});

it('shows single survey with details', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    SurveyQuestion::factory()->count(3)->for($survey)->create();

    $response = $this->getJson("/api/v1/admin/surveys/{$survey->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $survey->id)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'scope_type',
                'title',
                'description',
                'type',
                'is_active',
                'questions' => [
                    '*' => ['id', 'question', 'type', 'is_required'],
                ],
            ],
        ]);
});

it('updates a survey', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create([
        'is_active' => false,
    ]);

    $response = $this->putJson("/api/v1/admin/surveys/{$survey->id}", [
        'title_translations' => ['en' => 'Updated Title', 'ar' => 'عنوان محدث'],
        'is_active' => true,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title_translations.en', 'Updated Title')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'is_active' => true,
    ]);
});

it('rejects update when start date is after existing end date', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create([
        'start_at' => '2026-02-10',
        'end_at' => '2026-02-20',
    ]);

    $response = $this->putJson("/api/v1/admin/surveys/{$survey->id}", [
        'start_at' => '2026-02-25',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.details.start_at'))->not->toBeEmpty();
});

it('rejects update when end date is before existing start date', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create([
        'start_at' => '2026-02-10',
        'end_at' => '2026-02-20',
    ]);

    $response = $this->putJson("/api/v1/admin/surveys/{$survey->id}", [
        'end_at' => '2026-02-05',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.details.end_at'))->not->toBeEmpty();
});

it('rejects updating survey with more than 10 questions', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();

    $questions = array_map(
        static fn (int $i): array => [
            'question_translations' => [
                'en' => "Updated question $i",
                'ar' => "سؤال محدث $i",
            ],
            'type' => SurveyQuestionType::Text->value,
            'is_required' => true,
            'order_index' => $i,
        ],
        range(1, 11)
    );

    $response = $this->putJson("/api/v1/admin/surveys/{$survey->id}", [
        'questions' => $questions,
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');

    expect($response->json('error.details.questions'))->not->toBeEmpty();
});

it('deletes a survey', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();

    $response = $this->deleteJson("/api/v1/admin/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('surveys', ['id' => $survey->id]);
});

it('closes a survey', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create(['is_active' => true]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/close", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'is_active' => false,
    ]);
});

it('returns 404 for non-existent survey', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/surveys/999999', $this->adminHeaders());

    $response->assertNotFound();
});

it('filters surveys by is_active', function (): void {
    $this->asAdmin();
    Survey::factory()->system()->active()->count(2)->create();
    Survey::factory()->system()->inactive()->count(3)->create();

    $response = $this->getJson('/api/v1/admin/surveys?is_active=1', $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters surveys by type', function (): void {
    $this->asAdmin();
    Survey::factory()->system()->feedback()->count(2)->create();
    Survey::factory()->system()->poll()->count(1)->create();

    $response = $this->getJson('/api/v1/admin/surveys?type='.SurveyType::Feedback->value, $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});
