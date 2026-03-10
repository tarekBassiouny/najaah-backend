<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'assignments');

it('lists assignments for enrolled student', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $assignment->id);
});

it('returns empty list when no assignments exist', function (): void {
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

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');
});

it('denies assignment list for non-enrolled student', function (): void {
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

    Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('denies assignment list for non-student users', function (): void {
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

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('excludes inactive assignments from list', function (): void {
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

    Assignment::factory()->forCourse($course)->inactive()->create();
    $activeAssignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeAssignment->id);
});

it('shows assignment details for enrolled student', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $assignment->id);
});

it('denies assignment details for non-enrolled student', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_ENROLLED');
});

it('returns not found for inactive assignment', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->inactive()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}");

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_AVAILABLE');
});

it('returns not found for assignment from wrong center', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns my submission for an assignment', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $submission = AssignmentSubmission::factory()->create([
        'assignment_id' => $assignment->id,
        'user_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'center_id' => $center->id,
        'status' => SubmissionStatus::Submitted,
        'text_content' => 'My submission content',
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}/my-submission");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $submission->id);
});

it('returns not found when no submission exists', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}/my-submission");

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NO_SUBMISSION');
});

it('returns assignments ordered by order_index', function (): void {
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

    $assignment3 = Assignment::factory()->forCourse($course)->active()->create(['order_index' => 3]);
    $assignment1 = Assignment::factory()->forCourse($course)->active()->create(['order_index' => 1]);
    $assignment2 = Assignment::factory()->forCourse($course)->active()->create(['order_index' => 2]);

    $this->asApiUser($student);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/courses/{$course->id}/assignments");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $assignment1->id)
        ->assertJsonPath('data.1.id', $assignment2->id)
        ->assertJsonPath('data.2.id', $assignment3->id);
});

it('denies my submission for non-student users', function (): void {
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

    $assignment = Assignment::factory()->forCourse($course)->active()->create();

    $this->asApiUser($user);

    $response = $this->apiGet("/api/v1/centers/{$center->id}/assignments/{$assignment->id}/my-submission");

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});
