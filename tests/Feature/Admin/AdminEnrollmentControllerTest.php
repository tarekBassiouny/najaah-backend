<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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
                '*' => ['id', 'status', 'user_id', 'course_id', 'center_id', 'reason', 'enrolled_at', 'created_at'],
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

it('filters enrollments by search on student fields', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $matchedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'name' => 'Ahmed Hassan',
        'phone' => '01012345678',
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'name' => 'Sara Ali',
        'phone' => '01155555555',
    ]);

    Enrollment::factory()->create(['center_id' => $center->id, 'user_id' => $matchedStudent->id]);
    Enrollment::factory()->create(['center_id' => $center->id, 'user_id' => $otherStudent->id]);

    $byName = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments?search=Ahmed", $this->adminHeaders());
    $byName->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $matchedStudent->id);

    $byPhone = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments?search=0101", $this->adminHeaders());
    $byPhone->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $matchedStudent->id);
});

it('lists enrollments in system scope with center filter', function (): void {
    $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    Enrollment::factory()->create([
        'center_id' => $centerA->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    Enrollment::factory()->create([
        'center_id' => $centerB->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->getJson("/api/v1/admin/enrollments?center_id={$centerA->id}&status=ACTIVE", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id)
        ->assertJsonPath('data.0.status', 'ACTIVE');
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
        'reason' => 'Interested in this course',
    ]);

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/enrollments/{$enrollment->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $enrollment->id)
        ->assertJsonPath('data.user_id', $student->id)
        ->assertJsonPath('data.course_id', $course->id)
        ->assertJsonPath('data.student.email', $student->email)
        ->assertJsonPath('data.student.phone', $student->phone)
        ->assertJsonPath('data.center.id', $center->id)
        ->assertJsonPath('data.reason', 'Interested in this course')
        ->assertJsonPath('data.created_at', $enrollment->created_at?->toJSON())
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'status', 'user_id', 'course_id', 'center_id', 'reason', 'enrolled_at', 'created_at', 'course', 'student'],
        ]);
});

it('does not duplicate student object as user in enrollment response', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);

    $response = $this->getJson("/api/v1/admin/enrollments/{$enrollment->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.student.id', $student->id)
        ->assertJsonMissingPath('data.user');
});

it('returns center summary even if center is soft deleted', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $student = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'user_id' => $student->id,
        'course_id' => $course->id,
    ]);

    $center->delete();

    $response = $this->getJson("/api/v1/admin/enrollments/{$enrollment->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.center_id', $center->id)
        ->assertJsonPath('data.center.id', $center->id);
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

it('supports bulk status update with updated skipped and failed results', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $toUpdate = Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);
    $alreadyCancelled = Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_CANCELLED,
    ]);

    $response = $this->postJson('/api/v1/admin/enrollments/bulk-status', [
        'status' => 'CANCELLED',
        'enrollment_ids' => [$toUpdate->id, $alreadyCancelled->id, 999999],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('enrollments', [
        'id' => $toUpdate->id,
        'status' => Enrollment::STATUS_CANCELLED,
    ]);
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

it('allows centerless non-super admin with enrollment permission to list system enrollments', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'enrollment.manage'], [
        'description' => 'Permission: enrollment.manage',
    ]);
    $role = Role::firstOrCreate(['slug' => 'enrollment_manager'], [
        'name' => 'enrollment manager',
        'name_translations' => ['en' => 'enrollment manager', 'ar' => 'enrollment manager'],
        'description_translations' => ['en' => 'Enrollment management role', 'ar' => 'Enrollment management role'],
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    Enrollment::factory()->create(['center_id' => $centerA->id]);
    Enrollment::factory()->create(['center_id' => $centerB->id]);

    $response = $this->getJson('/api/v1/admin/enrollments', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data');
});

it('allows centerless non-super admin with enrollment permission to update enrollment in system scope', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'enrollment.manage'], [
        'description' => 'Permission: enrollment.manage',
    ]);
    $role = Role::firstOrCreate(['slug' => 'enrollment_manager'], [
        'name' => 'enrollment manager',
        'name_translations' => ['en' => 'enrollment manager', 'ar' => 'enrollment manager'],
        'description_translations' => ['en' => 'Enrollment management role', 'ar' => 'Enrollment management role'],
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => null,
    ]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $center = Center::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'center_id' => $center->id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $response = $this->putJson("/api/v1/admin/enrollments/{$enrollment->id}", [
        'status' => 'CANCELLED',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'CANCELLED');
});

it('counts unique user ids for bulk enroll total', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $course = Course::factory()->create(['center_id' => $center->id]);
    $studentA = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);
    $studentB = User::factory()->create(['is_student' => true, 'center_id' => $center->id]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/enrollments/bulk", [
        'course_id' => $course->id,
        'user_ids' => [$studentA->id, $studentA->id, $studentB->id],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.approved', 2);
});
