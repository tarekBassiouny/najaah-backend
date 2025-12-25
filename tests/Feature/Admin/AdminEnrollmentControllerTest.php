<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('enrollments', 'admin');

it('allows admin to create enrollment', function (): void {
    $admin = $this->asAdmin();
    $center = \App\Models\Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson('/api/v1/admin/enrollments', [
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
    $center = \App\Models\Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->postJson('/api/v1/admin/enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('allows admin to update enrollment status', function (): void {
    $admin = $this->asAdmin();
    $center = \App\Models\Center::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->putJson("/api/v1/admin/enrollments/{$enrollment->id}", [
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
    $center = \App\Models\Center::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
    ]);

    $response = $this->deleteJson("/api/v1/admin/enrollments/{$enrollment->id}", [], $this->adminHeaders());

    $response->assertNoContent();
    $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);
});

it('allows enrollment management across centers', function (): void {
    $this->asAdmin();
    $center = \App\Models\Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);

    $response = $this->postJson('/api/v1/admin/enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertCreated();
});
