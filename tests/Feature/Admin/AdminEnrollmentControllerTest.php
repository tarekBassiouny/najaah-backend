<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('enrollments', 'admin');

it('lists enrollments for admin', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    Enrollment::factory()->count(3)->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'status', 'user_id', 'course_id', 'center_id', 'enrolled_at'],
            ],
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ]);
});

it('filters enrollments by status', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_CANCELLED,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments?status=ACTIVE", $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.status'))->toBe('ACTIVE');
});

it('filters enrollments by course_id', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $course1 = Course::factory()->create(['center_id' => $center->id]);
    $course2 = Course::factory()->create(['center_id' => $center->id]);
    Enrollment::factory()->create(['center_id' => $center->id, 'course_id' => $course1->id]);
    Enrollment::factory()->create(['center_id' => $center->id, 'course_id' => $course2->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments?course_id={$course1->id}", $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.course_id'))->toBe($course1->id);
});

it('filters enrollments by user_id', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student1 = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $student2 = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    Enrollment::factory()->create(['center_id' => $center->id, 'user_id' => $student1->id]);
    Enrollment::factory()->create(['center_id' => $center->id, 'user_id' => $student2->id]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments?user_id={$student1->id}", $this->adminHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.user_id'))->toBe($student1->id);
});

it('shows single enrollment', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments/{$enrollment->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $enrollment->id)
        ->assertJsonPath('data.user_id', $student->id)
        ->assertJsonPath('data.course_id', $course->id)
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'status', 'user_id', 'course_id', 'center_id', 'enrolled_at', 'course', 'student'],
        ]);
});

it('returns 404 for non-existent enrollment', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments/999999", $this->adminHeaders());

    $response->assertNotFound();
});

it('allows admin to create enrollment', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/enrollments", [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertCreated()->assertJsonPath('success', true);
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
});

it('rejects duplicate enrollments', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/enrollments", [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('allows admin to update enrollment status', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/enrollments/{$enrollment->id}", [
        'status' => 'CANCELLED',
    ], $this->adminHeaders());

    $response->assertOk()->assertJsonPath('data.status', 'CANCELLED');
    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
        'status' => Enrollment::STATUS_CANCELLED,
    ]);
});

it('allows admin to delete enrollment', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/enrollments/{$enrollment->id}", [], $this->adminHeaders());

    $response->assertNoContent();
    $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);
});

it('allows enrollment management across centers', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/enrollments", [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertCreated();
});
