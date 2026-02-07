<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('students', 'admin');

it('denies student access without permission', function (): void {
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/students', [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('allows super admin to create and delete students', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();

    $create = $this->postJson('/api/v1/admin/students', [
        'name' => 'Student One',
        'email' => 'student.one@example.com',
        'phone' => '19990000010',
        'country_code' => '+20',
        'center_id' => $center->id,
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Student One');

    $studentId = $create->json('data.id');

    $this->assertDatabaseHas('users', [
        'id' => $studentId,
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $this->assertDatabaseHas('user_centers', [
        'user_id' => $studentId,
        'center_id' => $center->id,
        'type' => 'student',
    ]);

    $delete = $this->deleteJson("/api/v1/admin/students/{$studentId}", [], $this->adminHeaders());

    $delete->assertStatus(204);
    $this->assertSoftDeleted('users', ['id' => $studentId]);
});

it('prevents non-super admins from creating students', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson('/api/v1/admin/students', [
        'name' => 'Student Two',
        'email' => 'student.two@example.com',
        'phone' => '19990000011',
        'country_code' => '+20',
        'center_id' => $center->id,
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('filters students by center for super admins', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    User::factory()->create([
        'name' => 'Center A Student',
        'is_student' => true,
        'center_id' => $centerA->id,
        'phone' => '19990000012',
    ]);
    User::factory()->create([
        'name' => 'Center B Student',
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000013',
    ]);

    $response = $this->getJson('/api/v1/admin/students?center_id='.$centerA->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

it('scopes students to admin center', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$centerA->id => ['type' => 'admin']]);

    User::factory()->create([
        'name' => 'Center A Student',
        'is_student' => true,
        'center_id' => $centerA->id,
        'phone' => '19990000014',
    ]);
    User::factory()->create([
        'name' => 'Center B Student',
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000015',
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/students?center_id='.$centerB->id, [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

it('filters students by status and search', function (): void {
    $this->asAdmin();

    User::factory()->create([
        'name' => 'Alpha Student',
        'email' => 'alpha.student@example.com',
        'is_student' => true,
        'status' => 0,
        'phone' => '19990000016',
    ]);
    User::factory()->create([
        'name' => 'Beta Student',
        'email' => 'beta.student@example.com',
        'is_student' => true,
        'status' => 1,
        'phone' => '19990000017',
    ]);

    $byStatus = $this->getJson('/api/v1/admin/students?status=0', $this->adminHeaders());

    $byStatus->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 0);

    $bySearch = $this->getJson('/api/v1/admin/students?search=Alpha', $this->adminHeaders());

    $bySearch->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Student');
});

it('includes analytics summary in student list responses', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $course = Course::factory()->for($center, 'center')->create([
        'center_id' => $center->id,
        'category_id' => \App\Models\Category::factory()->for($center, 'center'),
        'created_by' => User::factory()->for($center, 'center'),
    ]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'duration_seconds' => 1000,
    ]);
    $course->videos()->attach($video->id);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000050',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $student->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $center->id,
        'status' => \App\Enums\EnrollmentStatus::Active->value,
        'enrolled_at' => now()->subDay(),
    ]);

    \App\Models\PlaybackSession::factory()->create([
        'user_id' => $student->id,
        'video_id' => $video->id,
        'course_id' => $course->id,
        'device_id' => $device->id,
        'started_at' => now()->subHours(2),
        'ended_at' => now()->subHour(),
        'watch_duration' => 900,
        'progress_percent' => 70,
        'is_full_play' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/students', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.0.analytics.active_enrollments', 1)
        ->assertJsonPath('data.0.analytics.viewed_videos', 1)
        ->assertJsonPath('data.0.analytics.total_sessions', 1);
});

it('updates students within the admin center only', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $centerA->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$centerA->id => ['type' => 'admin']]);

    $studentA = User::factory()->create([
        'name' => 'Center A Student',
        'is_student' => true,
        'center_id' => $centerA->id,
        'phone' => '19990000018',
    ]);
    $studentB = User::factory()->create([
        'name' => 'Center B Student',
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000019',
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $updated = $this->putJson("/api/v1/admin/students/{$studentA->id}", [
        'name' => 'Updated Student',
        'status' => 0,
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $updated->assertOk()
        ->assertJsonPath('data.name', 'Updated Student')
        ->assertJsonPath('data.status', 0);

    $blocked = $this->putJson("/api/v1/admin/students/{$studentB->id}", [
        'name' => 'Blocked Student',
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $blocked->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('allows updating inactive students', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $student = User::factory()->create([
        'name' => 'Inactive Student',
        'is_student' => true,
        'center_id' => $center->id,
        'status' => \App\Enums\UserStatus::Inactive->value,
        'phone' => '19990000021',
    ]);

    $updated = $this->putJson("/api/v1/admin/students/{$student->id}", [
        'name' => 'Reactivated Student',
        'status' => \App\Enums\UserStatus::Active->value,
    ], $this->adminHeaders());

    $updated->assertOk()
        ->assertJsonPath('data.name', 'Reactivated Student')
        ->assertJsonPath('data.status', \App\Enums\UserStatus::Active->value);
});
