<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('enrollments', 'admin');

it('allows admin to create enrollment', function (): void {
    $admin = $this->asAdmin();
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(['center_id' => $admin->center_id]);

    $response = $this->postJson('/admin/enrollments', [
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
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(['center_id' => $admin->center_id]);
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->postJson('/admin/enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertStatus(422);
});

it('allows admin to update enrollment status', function (): void {
    $admin = $this->asAdmin();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $admin->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->putJson("/admin/enrollments/{$enrollment->id}", [
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
    $enrollment = Enrollment::factory()->create([
        'center_id' => $admin->center_id,
    ]);

    $response = $this->deleteJson("/admin/enrollments/{$enrollment->id}", [], $this->adminHeaders());

    $response->assertNoContent();
    $this->assertSoftDeleted('enrollments', ['id' => $enrollment->id]);
});

it('blocks enrollment management across centers', function (): void {
    $this->asAdmin();
    $student = User::factory()->create(['is_student' => true]);
    $course = Course::factory()->create(); // different center than admin by default

    $response = $this->postJson('/admin/enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'ACTIVE',
    ], $this->adminHeaders());

    $response->assertStatus(403);
});
