<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Instructors;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseInstructorActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'is_student' => false,
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_assign_instructor_to_course(): void
    {
        $admin = $this->actingAsAdmin();
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

        $this->assertDatabaseHas('course_instructors', [
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'deleted_at' => null,
        ]);
    }

    public function test_remove_instructor_from_course_and_update_primary(): void
    {
        $admin = $this->actingAsAdmin();
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

        $this->assertSoftDeleted('course_instructors', [
            'course_id' => $course->id,
            'instructor_id' => $lead->id,
        ]);

        $course->refresh();
        $this->assertSame($assistant->id, $course->primary_instructor_id);
    }

    public function test_cannot_assign_non_existing_instructor(): void
    {
        $this->actingAsAdmin();
        $course = Course::factory()->create();

        $response = $this->postJson('/api/v1/courses/'.$course->id.'/instructors', [
            'instructor_id' => 999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_course_response_includes_instructors(): void
    {
        $admin = $this->actingAsAdmin();
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
    }

    public function test_primary_instructor_is_set_on_first_assignment(): void
    {
        $admin = $this->actingAsAdmin();
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
        $this->assertSame($instructor->id, $course->primary_instructor_id);
    }
}
