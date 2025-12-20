<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
    ]);

    $updated->assertOk()
        ->assertJsonPath('data.name', 'Updated Student')
        ->assertJsonPath('data.status', 0);

    $blocked = $this->putJson("/api/v1/admin/students/{$studentB->id}", [
        'name' => 'Blocked Student',
    ], [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ]);

    $blocked->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});
