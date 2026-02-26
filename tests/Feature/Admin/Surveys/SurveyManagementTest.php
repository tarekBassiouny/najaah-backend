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
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
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
                '*' => ['id', 'scope_type', 'title', 'type', 'is_active', 'assignments', 'submitted_users_count'],
            ],
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ]);
});

it('lists surveys with assignments and submitted users count', function (): void {
    $this->asAdmin();

    $survey = Survey::factory()->system()->create();
    $assignedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $assignedStudent->id,
    ]);

    $responseCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $responseCenter->id,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $responseCenter->id,
    ]);

    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $studentA->id,
        'center_id' => $responseCenter->id,
    ]);
    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $studentB->id,
        'center_id' => $responseCenter->id,
    ]);

    $response = $this->getJson('/api/v1/admin/surveys', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $survey->id)
        ->assertJsonPath('data.0.assignments.0.type', SurveyAssignableType::User->value)
        ->assertJsonPath('data.0.submitted_users_count', 2)
        ->assertJsonPath('data.0.responses_count', 2);
});

it('super admin sees system surveys on system endpoint', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    Survey::factory()->system()->count(2)->create();
    Survey::factory()->center($center)->count(3)->create();

    // System endpoint returns only system surveys
    $response = $this->getJson('/api/v1/admin/surveys', $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('super admin can filter surveys by scope type', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
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

    User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $unbrandedCenter = Center::factory()->create(['type' => CenterType::Unbranded]);
    User::factory()->create([
        'is_student' => true,
        'center_id' => $unbrandedCenter->id,
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
        ->assertJsonPath('data.assignments.0.assignable_id', 0)
        ->assertJsonPath('data.assignments.0.assignable_name', 'All Students');
});

it('creates a center survey when assignment id is a numeric string', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
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

it('rejects creating center survey for unbranded center', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys", [
        'title_translations' => ['en' => 'Unbranded center survey', 'ar' => 'استبيان مركز غير مخصص'],
        'type' => SurveyType::Feedback->value,
        'is_active' => true,
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
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Center surveys are allowed only for branded centers.');
});

it('rejects listing center surveys for unbranded center', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/surveys", $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Center surveys are allowed only for branded centers.');
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

it('updates system survey status via dedicated endpoint', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create([
        'is_active' => false,
    ]);

    $response = $this->putJson("/api/v1/admin/surveys/{$survey->id}/status", [
        'is_active' => true,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $survey->id)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'is_active' => true,
    ]);
});

it('updates center survey status via dedicated endpoint', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $survey = Survey::factory()->center($center)->create([
        'is_active' => false,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/status", [
        'is_active' => true,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $survey->id)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'is_active' => true,
    ]);
});

it('bulk updates system survey statuses', function (): void {
    $this->asAdmin();

    $toUpdate = Survey::factory()->system()->create(['is_active' => true]);
    $alreadyTargetStatus = Survey::factory()->system()->create(['is_active' => false]);
    $centerSurvey = Survey::factory()->center(Center::factory()->create(['type' => CenterType::Branded]))->create(['is_active' => true]);

    $response = $this->postJson('/api/v1/admin/surveys/bulk-status', [
        'is_active' => false,
        'survey_ids' => [
            $toUpdate->id,
            $alreadyTargetStatus->id,
            $centerSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 2);

    $this->assertDatabaseHas('surveys', [
        'id' => $toUpdate->id,
        'is_active' => false,
    ]);
});

it('bulk updates center survey statuses', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);

    $toUpdate = Survey::factory()->center($center)->create(['is_active' => true]);
    $alreadyTargetStatus = Survey::factory()->center($center)->create(['is_active' => false]);
    $otherCenterSurvey = Survey::factory()->center($otherCenter)->create(['is_active' => true]);
    $systemSurvey = Survey::factory()->system()->create(['is_active' => true]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/bulk-status", [
        'is_active' => false,
        'survey_ids' => [
            $toUpdate->id,
            $alreadyTargetStatus->id,
            $otherCenterSurvey->id,
            $systemSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 5)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 3);

    $this->assertDatabaseHas('surveys', [
        'id' => $toUpdate->id,
        'is_active' => false,
    ]);
});

it('bulk closes system surveys', function (): void {
    $this->asAdmin();

    $toClose = Survey::factory()->system()->create(['is_active' => true]);
    $alreadyClosed = Survey::factory()->system()->create(['is_active' => false]);
    $centerSurvey = Survey::factory()->center(Center::factory()->create(['type' => CenterType::Branded]))->create(['is_active' => true]);

    $response = $this->postJson('/api/v1/admin/surveys/bulk-close', [
        'survey_ids' => [
            $toClose->id,
            $alreadyClosed->id,
            $centerSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.closed', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 2);

    $this->assertDatabaseHas('surveys', [
        'id' => $toClose->id,
        'is_active' => false,
    ]);
});

it('bulk closes center surveys', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);

    $toClose = Survey::factory()->center($center)->create(['is_active' => true]);
    $alreadyClosed = Survey::factory()->center($center)->create(['is_active' => false]);
    $otherCenterSurvey = Survey::factory()->center($otherCenter)->create(['is_active' => true]);
    $systemSurvey = Survey::factory()->system()->create(['is_active' => true]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/bulk-close", [
        'survey_ids' => [
            $toClose->id,
            $alreadyClosed->id,
            $otherCenterSurvey->id,
            $systemSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 5)
        ->assertJsonPath('data.counts.closed', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 3);

    $this->assertDatabaseHas('surveys', [
        'id' => $toClose->id,
        'is_active' => false,
    ]);
});

it('bulk deletes system surveys with safety checks', function (): void {
    $this->asAdmin();
    $responseCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $responseStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $responseCenter->id,
    ]);

    $deletable = Survey::factory()->system()->create(['is_active' => false]);
    $active = Survey::factory()->system()->create(['is_active' => true]);
    $withResponses = Survey::factory()->system()->create(['is_active' => false]);
    $centerSurvey = Survey::factory()->center(Center::factory()->create(['type' => CenterType::Branded]))->create(['is_active' => false]);

    SurveyResponse::factory()->create([
        'survey_id' => $withResponses->id,
        'user_id' => $responseStudent->id,
        'center_id' => $responseCenter->id,
    ]);

    $response = $this->postJson('/api/v1/admin/surveys/bulk-delete', [
        'survey_ids' => [
            $deletable->id,
            $active->id,
            $withResponses->id,
            $centerSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 5)
        ->assertJsonPath('data.counts.deleted', 1)
        ->assertJsonPath('data.counts.skipped', 2)
        ->assertJsonPath('data.counts.failed', 2)
        ->assertJsonPath('data.skipped.0.survey_id', $active->id)
        ->assertJsonPath('data.skipped.1.survey_id', $withResponses->id);

    $this->assertSoftDeleted('surveys', ['id' => $deletable->id]);
    $this->assertDatabaseHas('surveys', ['id' => $active->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('surveys', ['id' => $withResponses->id, 'deleted_at' => null]);
});

it('bulk deletes center surveys with safety checks', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $responseStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $deletable = Survey::factory()->center($center)->create(['is_active' => false]);
    $active = Survey::factory()->center($center)->create(['is_active' => true]);
    $withResponses = Survey::factory()->center($center)->create(['is_active' => false]);
    $otherCenterSurvey = Survey::factory()->center($otherCenter)->create(['is_active' => false]);
    $systemSurvey = Survey::factory()->system()->create(['is_active' => false]);

    SurveyResponse::factory()->create([
        'survey_id' => $withResponses->id,
        'user_id' => $responseStudent->id,
        'center_id' => $center->id,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/bulk-delete", [
        'survey_ids' => [
            $deletable->id,
            $active->id,
            $withResponses->id,
            $otherCenterSurvey->id,
            $systemSurvey->id,
            999999,
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 6)
        ->assertJsonPath('data.counts.deleted', 1)
        ->assertJsonPath('data.counts.skipped', 2)
        ->assertJsonPath('data.counts.failed', 3)
        ->assertJsonPath('data.skipped.0.survey_id', $active->id)
        ->assertJsonPath('data.skipped.1.survey_id', $withResponses->id);

    $this->assertSoftDeleted('surveys', ['id' => $deletable->id]);
    $this->assertDatabaseHas('surveys', ['id' => $active->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('surveys', ['id' => $withResponses->id, 'deleted_at' => null]);
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
    $survey = Survey::factory()->system()->create([
        'is_active' => false,
    ]);

    $response = $this->deleteJson("/api/v1/admin/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('surveys', ['id' => $survey->id]);
});

it('rejects deleting an active survey', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create([
        'is_active' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Active survey cannot be deleted. Close it first.');

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'deleted_at' => null,
    ]);
});

it('rejects deleting a survey with responses', function (): void {
    $this->asAdmin();

    $responseCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $responseStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $responseCenter->id,
    ]);

    $survey = Survey::factory()->system()->create([
        'is_active' => false,
    ]);

    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $responseStudent->id,
        'center_id' => $responseCenter->id,
    ]);

    $response = $this->deleteJson("/api/v1/admin/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Survey with responses cannot be deleted.');

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'deleted_at' => null,
    ]);
});

it('deletes a center survey', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $survey = Survey::factory()->center($center)->create([
        'is_active' => false,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertSoftDeleted('surveys', ['id' => $survey->id]);
});

it('rejects deleting an active center survey', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $survey = Survey::factory()->center($center)->create([
        'is_active' => true,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Active survey cannot be deleted. Close it first.');

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'deleted_at' => null,
    ]);
});

it('rejects deleting a center survey with responses', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $survey = Survey::factory()->center($center)->create([
        'is_active' => false,
    ]);

    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}", [], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Survey with responses cannot be deleted.');

    $this->assertDatabaseHas('surveys', [
        'id' => $survey->id,
        'deleted_at' => null,
    ]);
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

it('filters surveys by is_mandatory', function (): void {
    $this->asAdmin();
    Survey::factory()->system()->create(['is_mandatory' => true]);
    Survey::factory()->system()->create(['is_mandatory' => true]);
    Survey::factory()->system()->create(['is_mandatory' => false]);

    $response = $this->getJson('/api/v1/admin/surveys?is_mandatory=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('accepts true and false string boolean filters from query params', function (): void {
    $this->asAdmin();

    $matchingSurvey = Survey::factory()->system()->create([
        'is_active' => true,
        'is_mandatory' => true,
        'start_at' => '2026-02-17',
    ]);

    Survey::factory()->system()->create([
        'is_active' => false,
        'is_mandatory' => true,
        'start_at' => '2026-02-17',
    ]);

    Survey::factory()->system()->create([
        'is_active' => true,
        'is_mandatory' => false,
        'start_at' => '2026-02-17',
    ]);

    Survey::factory()->system()->create([
        'is_active' => true,
        'is_mandatory' => true,
        'start_at' => '2026-02-16',
    ]);

    $response = $this->getJson(
        '/api/v1/admin/surveys?page=1&per_page=20&is_active=true&is_mandatory=true&start_from=2026-02-17',
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingSurvey->id);
});

it('filters surveys by start date range', function (): void {
    $this->asAdmin();

    $matchingSurvey = Survey::factory()->system()->create([
        'start_at' => '2026-03-10',
    ]);

    Survey::factory()->system()->create([
        'start_at' => '2026-02-10',
    ]);
    Survey::factory()->system()->create([
        'start_at' => null,
    ]);

    $response = $this->getJson('/api/v1/admin/surveys?start_from=2026-03-01&start_to=2026-03-31', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingSurvey->id);
});

it('filters surveys by end date range', function (): void {
    $this->asAdmin();

    $matchingSurvey = Survey::factory()->system()->create([
        'end_at' => '2026-03-20',
    ]);

    Survey::factory()->system()->create([
        'end_at' => '2026-04-20',
    ]);
    Survey::factory()->system()->create([
        'end_at' => null,
    ]);

    $response = $this->getJson('/api/v1/admin/surveys?end_from=2026-03-01&end_to=2026-03-31', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingSurvey->id);
});

it('rejects invalid survey list type filter', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/surveys?type=99', $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('rejects invalid survey list date formats', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/surveys?start_from=2026-03-01T00:00:00Z', $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_from']);
});

it('rejects invalid survey list date ranges', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/surveys?start_from=2026-03-31&start_to=2026-03-01', $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_from']);
});

it('filters system surveys by title search', function (): void {
    $this->asAdmin();

    $matchingSurvey = Survey::factory()->system()->create([
        'title_translations' => [
            'en' => 'Alpha Feedback Survey',
            'ar' => 'استبيان ألفا',
        ],
    ]);

    Survey::factory()->system()->create([
        'title_translations' => [
            'en' => 'Beta Survey',
            'ar' => 'استبيان بيتا',
        ],
    ]);

    $response = $this->getJson('/api/v1/admin/surveys?search=Alpha', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingSurvey->id);
});

it('filters center surveys by title search', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);

    $matchingSurvey = Survey::factory()->center($center)->create([
        'title_translations' => [
            'en' => 'Math Placement Survey',
            'ar' => 'استبيان الرياضيات',
        ],
    ]);

    Survey::factory()->center($center)->create([
        'title_translations' => [
            'en' => 'Science Survey',
            'ar' => 'استبيان العلوم',
        ],
    ]);

    Survey::factory()->center($otherCenter)->create([
        'title_translations' => [
            'en' => 'Math Other Center',
            'ar' => 'استبيان مركز آخر',
        ],
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/surveys?search=Math", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingSurvey->id);
});
