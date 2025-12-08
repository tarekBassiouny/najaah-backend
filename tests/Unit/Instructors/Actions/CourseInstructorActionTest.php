<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('instructors', 'actions');

test('assign instructor to course', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create();
    $instructor = Instructor::factory()->create([
        'center_id' => $course->center_id,
        'created_by' => $admin->id,
    ]);

    $response = $this->postJson('/api/v1/courses/'.$course->id.'/instructors', [
        'instructor_id' => $instructor->id,
        'role' => 'lead',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.primary_instructor_id', $instructor->id);

    assertDatabaseHas('course_instructors', [
        'course_id' => $course->id,
        'instructor_id' => $instructor->id,
        'deleted_at' => null,
    ]);
});

test('remove instructor from course and update primary', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create();
    $lead = Instructor::factory()->create([
        'center_id' => $course->center_id,
        'created_by' => $admin->id,
    ]);
    $assistant = Instructor::factory()->create([
        'center_id' => $course->center_id,
        'created_by' => $admin->id,
    ]);

    CourseInstructor::factory()->create([
        'course_id' => $course->id,
        'instructor_id' => $lead->id,
        'role' => 'lead',
    ]);
    CourseInstructor::factory()->create([
        'course_id' => $course->id,
        'instructor_id' => $assistant->id,
        'role' => 'assistant',
    ]);

    $course->update(['primary_instructor_id' => $lead->id]);

    $response = $this->deleteJson('/api/v1/courses/'.$course->id.'/instructors/'.$lead->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    assertSoftDeleted('course_instructors', [
        'course_id' => $course->id,
        'instructor_id' => $lead->id,
    ]);

    $course->refresh();
    expect($course->primary_instructor_id)->toBe($assistant->id);
});

test('cannot assign non existing instructor', function (): void {
    $this->asAdmin();
    $course = Course::factory()->create();

    $response = $this->postJson('/api/v1/courses/'.$course->id.'/instructors', [
        'instructor_id' => 999999,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('course response includes instructors', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create();
    $instructor = Instructor::factory()->create([
        'center_id' => $course->center_id,
        'created_by' => $admin->id,
    ]);

    $response = $this->postJson('/api/v1/courses/'.$course->id.'/instructors', [
        'instructor_id' => $instructor->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.instructors.0.id', $instructor->id);
});

test('primary instructor is set on first assignment', function (): void {
    $admin = $this->asAdmin();
    $course = Course::factory()->create([
        'primary_instructor_id' => null,
    ]);
    $instructor = Instructor::factory()->create([
        'center_id' => $course->center_id,
        'created_by' => $admin->id,
    ]);

    $this->postJson('/api/v1/courses/'.$course->id.'/instructors', [
        'instructor_id' => $instructor->id,
    ])->assertCreated();

    $course->refresh();
    expect($course->primary_instructor_id)->toBe($instructor->id);
});
