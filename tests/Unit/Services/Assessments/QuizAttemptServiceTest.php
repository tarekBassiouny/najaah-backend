<?php

declare(strict_types=1);

use App\Enums\AttemptScorePolicy;
use App\Enums\QuizAttemptStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Assessments\QuizAttemptService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class)->group('assessments', 'quiz', 'services', 'unit');

test('grade marks answers and computes score and pass state', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 3, 'is_published' => true]);
    $student = User::factory()->student()->create(['center_id' => $center->id]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $quiz = Quiz::factory()->forCourse($course)->create([
        'passing_score' => 70,
        'attempt_score_policy' => AttemptScorePolicy::Best,
    ]);

    $q1 = QuizQuestion::factory()->singleChoice()->create(['quiz_id' => $quiz->id, 'points' => 10]);
    $q1Correct = \App\Models\QuizAnswer::factory()->correct()->create(['quiz_question_id' => $q1->id]);
    \App\Models\QuizAnswer::factory()->incorrect()->create(['quiz_question_id' => $q1->id]);

    $q2 = QuizQuestion::factory()->singleChoice()->create(['quiz_id' => $quiz->id, 'points' => 20]);
    \App\Models\QuizAnswer::factory()->correct()->create(['quiz_question_id' => $q2->id]);
    $q2Wrong = \App\Models\QuizAnswer::factory()->incorrect()->create(['quiz_question_id' => $q2->id]);

    $attempt = QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'status' => QuizAttemptStatus::Submitted,
    ]);

    $service = app(QuizAttemptService::class);
    $service->saveAnswer($attempt, $q1, [$q1Correct->id]); // correct => 10
    $service->saveAnswer($attempt, $q2, [$q2Wrong->id]);   // wrong => 0

    $graded = $service->grade($attempt);

    expect((float) $graded->points_possible)->toBe(30.0)
        ->and((float) $graded->points_earned)->toBe(10.0)
        ->and((float) $graded->score)->toBe(33.33)
        ->and($graded->passed)->toBeFalse()
        ->and($graded->status)->toBe(QuizAttemptStatus::Graded);
});

test('calculate final score respects best latest and average policies', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 3, 'is_published' => true]);
    $student = User::factory()->student()->create(['center_id' => $center->id]);

    $quiz = Quiz::factory()->forCourse($course)->create([
        'attempt_score_policy' => AttemptScorePolicy::Best,
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
        'status' => QuizAttemptStatus::Graded,
        'score' => 40,
        'submitted_at' => now()->subMinutes(20),
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
        'status' => QuizAttemptStatus::Graded,
        'score' => 70,
        'submitted_at' => now()->subMinutes(10),
    ]);

    QuizAttempt::factory()->create([
        'quiz_id' => $quiz->id,
        'user_id' => $student->id,
        'center_id' => $center->id,
        'status' => QuizAttemptStatus::Graded,
        'score' => 60,
        'submitted_at' => now()->subMinutes(5),
    ]);

    $service = app(QuizAttemptService::class);

    $quiz->update(['attempt_score_policy' => AttemptScorePolicy::Best]);
    expect($service->calculateFinalScore($quiz->fresh(), $student))->toBe(70.0);

    $quiz->update(['attempt_score_policy' => AttemptScorePolicy::Latest]);
    expect($service->calculateFinalScore($quiz->fresh(), $student))->toBe(60.0);

    $quiz->update(['attempt_score_policy' => AttemptScorePolicy::Average]);
    expect($service->calculateFinalScore($quiz->fresh(), $student))->toBe(56.67);
});
