<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\SurveyAssignableType;
use App\Models\Center;
use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'surveys');

it('lists assigned surveys for a student', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $survey->id);
});

it('returns only the highest-priority assigned survey', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $nonMandatory = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => false,
        'end_at' => null,
    ]);
    $mandatoryLaterDeadline = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => true,
        'end_at' => now()->addDays(3),
    ]);
    $mandatoryEarlierDeadline = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => true,
        'end_at' => now()->addDay(),
    ]);

    foreach ([$nonMandatory, $mandatoryLaterDeadline, $mandatoryEarlierDeadline] as $survey) {
        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'assignable_type' => SurveyAssignableType::User,
            'assignable_id' => $student->id,
        ]);
    }

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mandatoryEarlierDeadline->id);
});

it('uses newest survey when priority ties and no deadline exists', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $olderSurvey = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => false,
        'end_at' => null,
    ]);
    $olderSurvey->forceFill(['created_at' => now()->subDays(2)])->save();

    $newerSurvey = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => false,
        'end_at' => null,
    ]);

    foreach ([$olderSurvey, $newerSurvey] as $survey) {
        SurveyAssignment::create([
            'survey_id' => $survey->id,
            'assignable_type' => SurveyAssignableType::User,
            'assignable_id' => $student->id,
        ]);
    }

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $newerSurvey->id);
});

it('lists survey assigned directly to the student', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create([
        'is_mandatory' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $student->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $survey->id);
});

it('does not list survey assigned directly to a different student', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $targetStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($otherStudent);

    $survey = Survey::factory()->center($center)->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User->value,
        'assignable_id' => $targetStudent->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('lists system survey for student without center when directly assigned', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->system()->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $survey->id);
});

it('submits system survey for student without center', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->system()->active()->create([
        'allow_multiple_submissions' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);
    $question = SurveyQuestion::factory()->text()->required()->for($survey)->create();

    $response = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 'System student response'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.survey_id', $survey->id);

    $this->assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
        'user_id' => $student->id,
        'center_id' => null,
    ]);
});

it('does not list system survey for student with center even when directly assigned', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->system()->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('lists video-assigned center survey only after full play', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create(['center_id' => $center->id]);

    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Video,
        'assignable_id' => $video->id,
    ]);

    $device = UserDevice::factory()->create(['user_id' => $student->id]);

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'is_full_play' => false,
    ]);

    $this->apiGet('/api/v1/surveys/assigned')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');

    PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'is_full_play' => true,
    ]);

    $this->apiGet('/api/v1/surveys/assigned')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $survey->id);
});

it('does not list surveys already submitted even when multiple submissions are enabled', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->create([
        'allow_multiple_submissions' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
    ]);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('shows a survey with submission status', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);
    SurveyQuestion::factory()->for($survey)->create();

    $response = $this->apiGet("/api/v1/surveys/{$survey->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    /** @var array<string, mixed> $payload */
    $payload = $response->json('data');
    $surveyData = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
    $hasSubmitted = $payload['has_submitted'] ?? ($surveyData['has_submitted'] ?? null);

    expect($surveyData['id'])->toBe($survey->id);
    expect($hasSubmitted)->toBeFalse();
});

it('returns not found for unavailable survey in show endpoint', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->inactive()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    $this->apiGet("/api/v1/surveys/{$survey->id}")
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('returns not found for surveys not assigned to the student in show endpoint', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $otherStudent->id,
    ]);

    $this->apiGet("/api/v1/surveys/{$survey->id}")
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('returns not found for surveys already submitted in show endpoint', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create([
        'allow_multiple_submissions' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);

    SurveyResponse::factory()->create([
        'survey_id' => $survey->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
    ]);

    $this->apiGet("/api/v1/surveys/{$survey->id}")
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('submits survey responses and blocks duplicate submissions even when multiple submissions are enabled', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create([
        'allow_multiple_submissions' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);
    $question = SurveyQuestion::factory()->text()->required()->for($survey)->create();

    $first = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 'Great content'],
        ],
    ]);

    $first->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.survey_id', $survey->id);

    $this->assertDatabaseHas('survey_responses', [
        'survey_id' => $survey->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
    ]);
    $this->assertDatabaseHas('survey_answers', [
        'survey_question_id' => $question->id,
        'answer_text' => 'Great content',
    ]);

    $second = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 'Second answer'],
        ],
    ]);

    $second->assertStatus(409)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'ALREADY_SUBMITTED');
});

it('forbids submitting surveys that are not assigned to the student', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $otherStudent->id,
    ]);
    $question = SurveyQuestion::factory()->text()->required()->for($survey)->create();

    $response = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 'Should fail'],
        ],
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

it('stores boolean yes/no answers as numeric values', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create([
        'allow_multiple_submissions' => true,
    ]);
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);
    $question = SurveyQuestion::factory()->yesNo()->required()->for($survey)->create();

    $response = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => true],
        ],
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('survey_answers', [
        'survey_question_id' => $question->id,
        'answer_number' => 1,
    ]);
});

it('returns validation error for invalid single-choice option', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Unbranded]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $this->asApiUser($student);

    $survey = Survey::factory()->center($center)->active()->create();
    SurveyAssignment::create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::User,
        'assignable_id' => $student->id,
    ]);
    $question = SurveyQuestion::factory()->singleChoice()->required()->for($survey)->create();
    SurveyQuestionOption::factory()->count(2)->create([
        'survey_question_id' => $question->id,
    ]);

    $response = $this->apiPost("/api/v1/surveys/{$survey->id}/submit", [
        'answers' => [
            ['question_id' => $question->id, 'answer' => 999999],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Invalid option selected');
});

it('forbids non-student users from accessing assigned surveys', function (): void {
    $user = User::factory()->create([
        'is_student' => false,
    ]);
    $this->asApiUser($user);

    $response = $this->apiGet('/api/v1/surveys/assigned');

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});
