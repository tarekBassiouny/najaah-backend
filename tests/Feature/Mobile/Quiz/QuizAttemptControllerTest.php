<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\QuizAttemptStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'quizzes', 'attempts');

function createQuizSetup(): array
{
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->active()->unlimited()->create([
        'passing_score' => 60,
    ]);

    $question = QuizQuestion::factory()->singleChoice()->active()->create([
        'quiz_id' => $quiz->id,
        'points' => 10,
        'order_index' => 0,
    ]);

    $correctAnswer = QuizAnswer::factory()->correct()->create([
        'quiz_question_id' => $question->id,
        'order_index' => 0,
    ]);

    $wrongAnswer = QuizAnswer::factory()->incorrect()->create([
        'quiz_question_id' => $question->id,
        'order_index' => 1,
    ]);

    return [
        'center' => $center,
        'course' => $course,
        'student' => $student,
        'enrollment' => $enrollment,
        'quiz' => $quiz,
        'question' => $question,
        'correctAnswer' => $correctAnswer,
        'wrongAnswer' => $wrongAnswer,
    ];
}

it('starts a new quiz attempt', function (): void {
    $setup = createQuizSetup();
    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Quiz attempt started')
        ->assertJsonPath('data.quiz_id', $setup['quiz']->id);

    $this->assertDatabaseHas('quiz_attempts', [
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'status' => QuizAttemptStatus::InProgress->value,
    ]);
});

it('resumes existing in-progress attempt instead of starting new', function (): void {
    $setup = createQuizSetup();
    $this->asApiUser($setup['student']);

    $existingAttempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
        'started_at' => now(),
    ]);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Resuming existing attempt')
        ->assertJsonPath('data.id', $existingAttempt->id);

    // Ensure no new attempt was created
    $this->assertDatabaseCount('quiz_attempts', 1);
});

it('denies start for non-enrolled student', function (): void {
    $setup = createQuizSetup();
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($otherStudent);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('denies start for inactive quiz', function (): void {
    $setup = createQuizSetup();
    $setup['quiz']->update(['is_active' => false]);
    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('denies start when no attempts remaining', function (): void {
    $setup = createQuizSetup();
    $setup['quiz']->update(['max_attempts' => 1]);

    // Create a completed attempt
    QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::Graded,
        'attempt_number' => 1,
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NO_ATTEMPTS_LEFT');
});

it('shows attempt details', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
        'started_at' => now(),
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiGet("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $attempt->id);
});

it('denies showing attempt from different student', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
    ]);

    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($otherStudent);

    $response = $this->apiGet("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('saves an answer during attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
        'started_at' => now(),
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/answers", [
        'question_id' => $setup['question']->id,
        'answer_ids' => [$setup['correctAnswer']->id],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.question_id', $setup['question']->id);

    $this->assertDatabaseHas('quiz_attempt_answers', [
        'quiz_attempt_id' => $attempt->id,
        'quiz_question_id' => $setup['question']->id,
    ]);
});

it('denies saving answer to closed attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::Submitted,
        'attempt_number' => 1,
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/answers", [
        'question_id' => $setup['question']->id,
        'answer_ids' => [$setup['correctAnswer']->id],
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'ATTEMPT_CLOSED');
});

it('denies saving answer to other students attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
    ]);

    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($otherStudent);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/answers", [
        'question_id' => $setup['question']->id,
        'answer_ids' => [$setup['correctAnswer']->id],
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('denies saving answer with invalid question', function (): void {
    $setup = createQuizSetup();

    // Create a question for a different quiz
    $otherQuiz = Quiz::factory()->forCourse($setup['course'])->active()->create();
    $otherQuestion = QuizQuestion::factory()->create(['quiz_id' => $otherQuiz->id]);
    $otherAnswer = QuizAnswer::factory()->create(['quiz_question_id' => $otherQuestion->id]);

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
        'started_at' => now(),
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/answers", [
        'question_id' => $otherQuestion->id,
        'answer_ids' => [$otherAnswer->id],
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'INVALID_QUESTION');
});

it('submits a quiz attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
        'started_at' => now(),
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/submit");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Quiz submitted successfully');

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt->id,
        'status' => QuizAttemptStatus::Graded->value,
    ]);
});

it('denies submitting already submitted attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::Submitted,
        'attempt_number' => 1,
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/submit");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'ALREADY_SUBMITTED');
});

it('denies submitting other students attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
    ]);

    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($otherStudent);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/submit");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('gets results of completed attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::Graded,
        'attempt_number' => 1,
        'score' => 80,
        'passed' => true,
        'submitted_at' => now(),
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiGet("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/results");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $attempt->id)
        ->assertJsonPath('data.score', 80)
        ->assertJsonPath('data.passed', true);
});

it('denies results of in-progress attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
    ]);

    $this->asApiUser($setup['student']);

    $response = $this->apiGet("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}/results");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_SUBMITTED');
});

it('denies non-student users from starting quiz', function (): void {
    $setup = createQuizSetup();
    $nonStudent = User::factory()->create([
        'is_student' => false,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($nonStudent);

    $response = $this->apiPost("/api/v1/centers/{$setup['center']->id}/assets/quiz/{$setup['quiz']->id}/attempts");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('denies non-student users from viewing attempt', function (): void {
    $setup = createQuizSetup();

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $setup['quiz']->id,
        'user_id' => $setup['student']->id,
        'enrollment_id' => $setup['enrollment']->id,
        'center_id' => $setup['center']->id,
        'status' => QuizAttemptStatus::InProgress,
        'attempt_number' => 1,
    ]);

    $nonStudent = User::factory()->create([
        'is_student' => false,
        'center_id' => $setup['center']->id,
    ]);
    $this->asApiUser($nonStudent);

    $response = $this->apiGet("/api/v1/centers/{$setup['center']->id}/assets/quiz/attempts/{$attempt->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});
