<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'enrollment-requests');

it('creates enrollment request when not enrolled', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/enroll-request", [
        'reason' => 'Interested in joining',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => Enrollment::STATUS_PENDING,
        'reason' => 'Interested in joining',
    ]);
});

it('blocks enrollment request when already enrolled', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/enroll-request");

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'ALREADY_ENROLLED');
});

it('blocks duplicate pending enrollment request', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Enrollment::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_PENDING,
        'enrolled_at' => now(),
    ]);

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/enroll-request");

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('blocks enrollment request on center mismatch', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);
    $course = Course::factory()->create([
        'center_id' => $centerA->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$centerB->id}/courses/{$course->id}/enroll-request");

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('blocks system students from requesting enrollment in branded center courses', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $course = Course::factory()->create([
        'center_id' => $center->id,
        'status' => 3,
        'is_published' => true,
    ]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiPost("/api/v1/centers/{$center->id}/courses/{$course->id}/enroll-request", [
        'reason' => 'Interested in joining',
    ], [
        'X-Api-Key' => (string) config('services.system_api_key'),
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});
