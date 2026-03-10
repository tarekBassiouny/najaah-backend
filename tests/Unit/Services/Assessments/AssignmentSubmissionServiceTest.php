<?php

declare(strict_types=1);

use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Assessments\AssignmentSubmissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class)->group('assessments', 'assignment', 'services', 'unit');

test('grade applies late penalty and updates pass decision', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 3, 'is_published' => true]);
    $student = User::factory()->student()->create(['center_id' => $center->id]);
    $grader = User::factory()->admin()->create(['center_id' => $center->id]);

    $assignment = Assignment::factory()->forCourse($course)->create([
        'max_points' => 100.0,
        'passing_score' => 60.0,
        'late_submission_allowed' => true,
        'late_penalty_percent' => 10.0, // per day
    ]);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $submission = AssignmentSubmission::factory()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'status' => SubmissionStatus::Submitted,
        'is_late' => true,
        'days_late' => 2, // 20% penalty
    ]);

    $service = app(AssignmentSubmissionService::class);
    $graded = $service->grade($submission, 90.0, 'Good work', $grader);

    expect($graded->status)->toBe(SubmissionStatus::Graded)
        ->and((float) $graded->score)->toBe(90.0)
        ->and((float) $graded->score_after_penalty)->toBe(72.0)
        ->and($graded->passed)->toBeTrue()
        ->and($graded->graded_by)->toBe($grader->id);
});

test('grade throws when submission is not gradable', function (): void {
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id, 'status' => 3, 'is_published' => true]);
    $student = User::factory()->student()->create(['center_id' => $center->id]);
    $grader = User::factory()->admin()->create(['center_id' => $center->id]);
    $assignment = Assignment::factory()->forCourse($course)->create();
    $enrollment = Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $submission = AssignmentSubmission::factory()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'status' => SubmissionStatus::Draft,
    ]);

    $service = app(AssignmentSubmissionService::class);

    expect(fn () => $service->grade($submission, 80.0, 'N/A', $grader))
        ->toThrow(\DomainException::class);
});
