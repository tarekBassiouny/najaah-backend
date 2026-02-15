<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\SurveyAssignableType;
use App\Models\Center;
use App\Models\Course;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('surveys', 'admin', 'assignments');

it('assigns system survey to unbranded center', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => $center->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Center->value,
        'assignable_id' => $center->id,
    ]);
});

it('prevents assigning system survey to branded center', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    $center = Center::factory()->create(['type' => CenterType::Branded]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => $center->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500); // InvalidArgumentException
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_id' => $center->id,
    ]);
});

it('assigns system survey to course in unbranded center', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Course->value,
        'assignable_id' => $course->id,
    ]);
});

it('prevents assigning system survey to course in branded center', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500); // InvalidArgumentException
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_id' => $course->id,
    ]);
});

it('assigns center survey to course within same center', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Course->value,
        'assignable_id' => $course->id,
    ]);
});

it('rejects section assignment type', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => 'section', 'id' => 1],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('prevents cross-center assignment', function (): void {
    $this->asAdmin();
    $center1 = Center::factory()->create();
    $center2 = Center::factory()->create();
    $survey = Survey::factory()->center($center1)->create();
    $course = Course::factory()->create(['center_id' => $center2->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center1->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500); // InvalidArgumentException
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_id' => $course->id,
    ]);
});

it('assigns multiple entities at once', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    $course1 = Course::factory()->create(['center_id' => $center->id]);
    $course2 = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course1->id],
            ['type' => SurveyAssignableType::Course->value, 'id' => $course2->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk();
    expect(SurveyAssignment::where('survey_id', $survey->id)->count())->toBe(2);
});

it('prevents duplicate assignments', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    $course = Course::factory()->create(['center_id' => $center->id]);

    // First assignment
    $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    // Second assignment (same)
    $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Course->value, 'id' => $course->id],
        ],
    ], $this->adminHeaders());

    expect(SurveyAssignment::where('survey_id', $survey->id)->count())->toBe(1);
});

it('rejects assignment to non-existent entity', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => 999999],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500);
});

it('rejects assigning center-scoped survey by center type', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => $center->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500);
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Center->value,
        'assignable_id' => $center->id,
    ]);
});

it('rejects assigning center survey to a different center', function (): void {
    $this->asAdmin();
    $center1 = Center::factory()->create();
    $center2 = Center::factory()->create();
    $survey = Survey::factory()->center($center1)->create();

    $response = $this->postJson("/api/v1/admin/centers/{$center1->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => $center2->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500);
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Center->value,
        'assignable_id' => $center2->id,
    ]);
});

it('returns warning when assigned cohort already has an active survey', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);

    $existingSurvey = Survey::factory()->system()->active()->create([
        'is_mandatory' => true,
    ]);
    $newSurvey = Survey::factory()->system()->active()->create();

    SurveyAssignment::create([
        'survey_id' => $existingSurvey->id,
        'assignable_type' => SurveyAssignableType::Center,
        'assignable_id' => $center->id,
    ]);

    $response = $this->postJson("/api/v1/admin/surveys/{$newSurvey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::Center->value, 'id' => $center->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('warnings.0.type', SurveyAssignableType::Center->value)
        ->assertJsonPath('warnings.0.id', $center->id)
        ->assertJsonPath('warnings.0.conflicting_count', 1);

    expect($response->json('warnings.0.conflicting_survey_ids'))->toContain($existingSurvey->id);
});

it('assigns center survey to multiple specific students', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    $studentA = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $studentB = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::User->value, 'id' => $studentA->id],
            ['type' => SurveyAssignableType::User->value, 'id' => $studentB->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $studentA->id,
    ]);
    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $studentB->id,
    ]);
});

it('rejects assigning survey directly to non-student user', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    $adminLikeUser = User::factory()->create(['is_student' => false, 'center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::User->value, 'id' => $adminLikeUser->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500);
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $adminLikeUser->id,
    ]);
});

it('rejects assigning center survey to student from different center', function (): void {
    $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $survey = Survey::factory()->center($centerA)->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $centerB->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$centerA->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::User->value, 'id' => $student->id],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(500);
    $this->assertDatabaseMissing('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $student->id,
    ]);
});

it('assigns system survey to student without center', function (): void {
    $this->asAdmin();
    $survey = Survey::factory()->system()->create();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $response = $this->postJson("/api/v1/admin/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::User->value, 'id' => $student->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('survey_assignments', [
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $student->id,
    ]);
});

it('supports all assignment in assign endpoint without id', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $survey = Survey::factory()->center($center)->create();
    User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    User::factory()->create(['is_student' => true, 'center_id' => Center::factory()->create()->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/surveys/{$survey->id}/assign", [
        'assignments' => [
            ['type' => SurveyAssignableType::All->value],
        ],
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('success', true);
    expect(SurveyAssignment::where('survey_id', $survey->id)->count())->toBe(2);
});
