<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\LearningAssetType;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
use App\Models\LearningAssetProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Assessments\AssessmentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('unit', 'assessments');

it('includes learning asset progress in course progress summary', function (): void {
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

    $quiz = Quiz::factory()->forCourse($course)->active()->required()->create();
    $assignment = Assignment::factory()->forCourse($course)->active()->required()->create();

    QuizAttempt::factory()->forQuiz($quiz)->forUser($student)->passed()->create([
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
    ]);
    AssignmentSubmission::factory()->forAssignment($assignment)->forUser($student)->passed()->create([
        'center_id' => $center->id,
        'enrollment_id' => $enrollment->id,
    ]);

    $summary = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Summary,
    ]);
    $flashcards = LearningAsset::factory()->published()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'asset_type' => LearningAssetType::Flashcards,
    ]);

    LearningAssetProgress::factory()->completed()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'learning_asset_id' => $summary->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
    ]);
    LearningAssetProgress::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'learning_asset_id' => $flashcards->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'progress_percent' => 50,
    ]);

    $summaryData = (new AssessmentProgressService)->getProgressSummary($student, $course);

    expect($summaryData['quizzes']['required_passed'])->toBe(1)
        ->and($summaryData['assignments']['required_passed'])->toBe(1)
        ->and($summaryData['learning_assets']['total'])->toBe(2)
        ->and($summaryData['learning_assets']['completed'])->toBe(1)
        ->and($summaryData['learning_assets']['in_progress'])->toBe(1)
        ->and((float) $summaryData['overall_completion_percentage'])->toBe(100.0)
        ->and((float) $summaryData['overall_content_completion_percentage'])->toBe(75.0)
        ->and($summaryData['all_required_passed'])->toBeTrue();
});
