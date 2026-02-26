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

    // Use branded center to test immediate user_centers association.
    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Branded->value]);

    $create = $this->postJson('/api/v1/admin/students', [
        'name' => 'Student One',
        'email' => 'student.one@example.com',
        'phone' => '1225291841',
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

    $delete->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);
    $this->assertSoftDeleted('users', ['id' => $studentId]);
});

it('attaches existing system student to unbranded center without creating duplicate user', function (): void {
    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Unbranded->value]);
    $this->asCenterAdmin($center);
    $student = User::factory()->create([
        'name' => 'Existing System Student',
        'is_student' => true,
        'center_id' => null,
        'phone' => '1225291901',
        'country_code' => '+20',
        'email' => 'existing.system.student@example.com',
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/students", [
        'name' => 'Should Not Duplicate',
        'email' => 'new.email@example.com',
        'phone' => '1225291901',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.center_id', null);

    expect(User::query()
        ->where('is_student', true)
        ->where('phone', '1225291901')
        ->where('country_code', '+20')
        ->count())->toBe(1);

    $this->assertDatabaseHas('user_centers', [
        'user_id' => $student->id,
        'center_id' => $center->id,
        'type' => 'student',
    ]);
});

it('creates system student then attaches to unbranded center when student does not exist', function (): void {
    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Unbranded->value]);
    $this->asCenterAdmin($center);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/students", [
        'name' => 'New System Student',
        'email' => 'new.system.student@example.com',
        'phone' => '1225291902',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'New System Student')
        ->assertJsonPath('data.center_id', null);

    $studentId = (int) $response->json('data.id');

    $this->assertDatabaseHas('users', [
        'id' => $studentId,
        'is_student' => true,
        'center_id' => null,
        'phone' => '1225291902',
    ]);

    $this->assertDatabaseHas('user_centers', [
        'user_id' => $studentId,
        'center_id' => $center->id,
        'type' => 'student',
    ]);
});

it('creates center-bound student for branded center route', function (): void {
    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Branded->value]);
    $this->asCenterAdmin($center);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/students", [
        'name' => 'Branded Center Student',
        'email' => 'branded.center.student@example.com',
        'phone' => '1225291903',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Branded Center Student')
        ->assertJsonPath('data.center_id', $center->id);

    $studentId = (int) $response->json('data.id');

    $this->assertDatabaseHas('users', [
        'id' => $studentId,
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '1225291903',
    ]);

    $this->assertDatabaseHas('user_centers', [
        'user_id' => $studentId,
        'center_id' => $center->id,
        'type' => 'student',
    ]);
});

it('validates student phone as base number and country code format', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $leadingZero = $this->postJson('/api/v1/admin/students', [
        'name' => 'Invalid Phone Student',
        'email' => 'invalid.phone@example.com',
        'phone' => '01225291841',
        'country_code' => '+20',
        'center_id' => $center->id,
    ], $this->adminHeaders());

    $leadingZero->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure([
            'error' => [
                'details' => ['phone'],
            ],
        ]);

    $withCountryInPhone = $this->postJson('/api/v1/admin/students', [
        'name' => 'Invalid Base Phone Student',
        'email' => 'invalid.base.phone@example.com',
        'phone' => '201225291841',
        'country_code' => '20',
        'center_id' => $center->id,
    ], $this->adminHeaders());

    $withCountryInPhone->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure([
            'error' => [
                'details' => ['phone', 'country_code'],
            ],
        ]);
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
        'phone' => '1225291842',
        'country_code' => '+20',
        'center_id' => $center->id,
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => config('services.system_api_key'),
    ]);

    $response->assertStatus(403)->assertJsonPath('error.code', 'SYSTEM_SCOPE_REQUIRED');
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

    // Center-scoped admin can only access their own center's students via center route
    $response = $this->getJson("/api/v1/admin/centers/{$centerA->id}/students", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $centerA->api_key,
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.center_id', $centerA->id);

    // Center-scoped admin cannot access other center's students
    $blockedResponse = $this->getJson("/api/v1/admin/centers/{$centerB->id}/students", [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $centerA->api_key,
    ]);

    $blockedResponse->assertForbidden();
});

it('filters students by status and student search fields', function (): void {
    $this->asAdmin();

    User::factory()->create([
        'name' => 'Alpha Student',
        'email' => 'alpha.search@example.com',
        'is_student' => true,
        'status' => 0,
        'phone' => '19991234016',
    ]);
    User::factory()->create([
        'name' => 'Beta Student',
        'email' => 'beta.search@example.com',
        'is_student' => true,
        'status' => 1,
        'phone' => '19995678017',
    ]);

    $byStatus = $this->getJson('/api/v1/admin/students?status=0', $this->adminHeaders());

    $byStatus->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 0);

    $byLegacySearch = $this->getJson('/api/v1/admin/students?search=Alpha', $this->adminHeaders());
    $byName = $this->getJson('/api/v1/admin/students?student_name=Alpha', $this->adminHeaders());
    $byPhone = $this->getJson('/api/v1/admin/students?student_phone=1234', $this->adminHeaders());
    $byEmail = $this->getJson('/api/v1/admin/students?student_email=alpha.search', $this->adminHeaders());

    $byLegacySearch->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Student');

    $byName->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Student');

    $byPhone->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Student');

    $byEmail->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Student');
});

it('filters students by center type (branded/unbranded)', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $centerStudentA = User::factory()->create([
        'name' => 'Center Student A',
        'is_student' => true,
        'center_id' => $centerA->id,
        'phone' => '19990000061',
    ]);
    $centerStudentB = User::factory()->create([
        'name' => 'Center Student B',
        'is_student' => true,
        'center_id' => $centerB->id,
        'phone' => '19990000062',
    ]);
    $systemStudent = User::factory()->create([
        'name' => 'System Student',
        'is_student' => true,
        'center_id' => null,
        'phone' => '19990000063',
    ]);

    $brandedResponse = $this->getJson('/api/v1/admin/students?type=branded&per_page=50', $this->adminHeaders());
    $brandedIds = collect($brandedResponse->json('data'))->pluck('id')->all();

    $brandedResponse->assertOk();
    expect($brandedIds)
        ->toContain($centerStudentA->id)
        ->toContain($centerStudentB->id)
        ->not->toContain($systemStudent->id);

    $unbrandedResponse = $this->getJson('/api/v1/admin/students?type=unbranded&per_page=50', $this->adminHeaders());
    $unbrandedIds = collect($unbrandedResponse->json('data'))->pluck('id')->all();

    $unbrandedResponse->assertOk();
    expect($unbrandedIds)
        ->toContain($systemStudent->id)
        ->not->toContain($centerStudentA->id)
        ->not->toContain($centerStudentB->id);
});

it('includes analytics summary in student list responses', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $lastLoginAt = now()->subMinutes(15)->startOfSecond();
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
        'last_login_at' => $lastLoginAt,
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
        ->assertJsonPath('data.0.analytics.total_sessions', 1)
        ->assertJsonPath('data.0.analytics.last_activity_at', $lastLoginAt->toIso8601String());
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

    // Center-scoped admin can update students within their center via center route
    $updated = $this->putJson("/api/v1/admin/centers/{$centerA->id}/students/{$studentA->id}", [
        'name' => 'Updated Student',
        'status' => 0,
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $centerA->api_key,
    ]);

    $updated->assertOk()
        ->assertJsonPath('data.name', 'Updated Student')
        ->assertJsonPath('data.status', 0);

    // Center-scoped admin cannot update students from other centers
    $blocked = $this->putJson("/api/v1/admin/centers/{$centerB->id}/students/{$studentB->id}", [
        'name' => 'Blocked Student',
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $centerA->api_key,
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

it('detaches unbranded student from center route without deleting user record', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Unbranded->value]);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'phone' => '19990000071',
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/students/{$student->id}", [], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'deleted_at' => null,
    ]);
    $this->assertSoftDeleted('user_centers', [
        'user_id' => $student->id,
        'center_id' => $center->id,
        'type' => 'student',
    ]);
});

it('deletes branded student from center route', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Branded->value]);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '19990000072',
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/students/{$student->id}", [], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    $this->assertSoftDeleted('users', ['id' => $student->id]);
});

it('bulk updates unbranded center students by pivot association', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'student.manage'], [
        'description' => 'Permission: student.manage',
    ]);
    $role = Role::factory()->create(['slug' => 'student_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create(['type' => \App\Enums\CenterType::Unbranded->value]);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $linkedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'status' => 1,
        'phone' => '19990000073',
    ]);
    $linkedStudent->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $notLinkedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'status' => 1,
        'phone' => '19990000074',
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/students/bulk-status", [
        'status' => 0,
        'student_ids' => [$linkedStudent->id, $notLinkedStudent->id],
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.failed', 1);

    expect((int) $linkedStudent->fresh()->status)->toBe(0);
    expect((int) $notLinkedStudent->fresh()->status)->toBe(1);
});
