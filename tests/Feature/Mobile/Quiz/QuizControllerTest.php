<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'quizzes');

function createQuizWithQuestions(Course $course, int $questionCount = 3): Quiz
{
    $quiz = Quiz::factory()->forCourse($course)->active()->create();

    for ($i = 0; $i < $questionCount; $i++) {
        $question = QuizQuestion::factory()->singleChoice()->active()->create([
            'quiz_id' => $quiz->id,
            'order_index' => $i,
        ]);

        // Create 4 answers, one correct
        QuizAnswer::factory()->correct()->create([
            'quiz_question_id' => $question->id,
            'order_index' => 0,
        ]);
        QuizAnswer::factory()->count(3)->create([
            'quiz_question_id' => $question->id,
        ]);
    }

    return $quiz;
}

it('lists quizzes for enrolled student', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $quiz->id);
});

it('returns empty list when no quizzes exist', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('denies quiz list for non-enrolled student', function (): void {
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

    createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('denies quiz list for non-student users', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $user = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $this->asApiUser($user);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('excludes inactive quizzes from list', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    Quiz::factory()->forCourse($course)->inactive()->create();
    $activeQuiz = createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeQuiz->id);
});

it('shows quiz info for enrolled student', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = createQuizWithQuestions($course, 5);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $quiz->id)
        ->assertJsonPath('data.total_questions', 5);
});

it('denies quiz info for non-enrolled student', function (): void {
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

    $quiz = createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('returns not found for inactive quiz', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->inactive()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('returns not found for quiz from wrong center', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $otherCenter = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $otherCenter->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $quiz = createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns my attempts for a quiz', function (): void {
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

    $quiz = createQuizWithQuestions($course);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}/my-attempts");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.attempts', [])
        ->assertJsonPath('data.stats.lowest_score', null)
        ->assertJsonPath('data.stats.average_score', null)
        ->assertJsonPath('data.stats.highest_score', null)
        ->assertJsonPath('data.stats.opened_count', 0)
        ->assertJsonPath('data.stats.completed_count', 0)
        ->assertJsonPath('data.stats.failed_count', 0);
});

it('returns attempt stats and per-attempt progress details', function (): void {
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

    $quiz = createQuizWithQuestions($course, 4);
    $questions = QuizQuestion::query()
        ->where('quiz_id', $quiz->id)
        ->orderBy('order_index')
        ->get();

    $inProgress = QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'attempt_number' => 1,
        'status' => 0,
        'score' => null,
        'passed' => false,
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'attempt_number' => 2,
        'status' => 3,
        'score' => 50.0,
        'passed' => false,
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'attempt_number' => 3,
        'status' => 3,
        'score' => 80.0,
        'passed' => true,
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'attempt_number' => 4,
        'status' => 2,
        'score' => null,
        'passed' => false,
    ]);

    QuizAttemptAnswer::factory()->forAttempt($inProgress)->forQuestion($questions[0])->create();
    QuizAttemptAnswer::factory()->forAttempt($inProgress)->forQuestion($questions[1])->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}/my-attempts");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.attempts.0.attempt_number', 1)
        ->assertJsonPath('data.attempts.0.answered_questions', 2)
        ->assertJsonPath('data.attempts.0.total_questions', 4)
        ->assertJsonPath('data.attempts.0.can_resume', true)
        ->assertJsonPath('data.stats.lowest_score', 50)
        ->assertJsonPath('data.stats.average_score', 65)
        ->assertJsonPath('data.stats.highest_score', 80)
        ->assertJsonPath('data.stats.opened_count', 4)
        ->assertJsonPath('data.stats.completed_count', 3)
        ->assertJsonPath('data.stats.failed_count', 2);
});

it('denies my attempts for non-student users', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);
    $user = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $quiz = createQuizWithQuestions($course);

    $this->asApiUser($user);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/quizzes/{$quiz->id}/my-attempts");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('returns quizzes ordered by order_index', function (): void {
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

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz3 = Quiz::factory()->forCourse($course)->active()->create(['order_index' => 3]);
    $quiz1 = Quiz::factory()->forCourse($course)->active()->create(['order_index' => 1]);
    $quiz2 = Quiz::factory()->forCourse($course)->active()->create(['order_index' => 2]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/quizzes");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $quiz1->id)
        ->assertJsonPath('data.1.id', $quiz2->id)
        ->assertJsonPath('data.2.id', $quiz3->id);
});
