<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('admin-roles');

it('denies role access without permission', function (): void {
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);
    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/roles', $this->adminHeaders());

    $response->assertStatus(403)->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('lists roles with permission', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'support_admin']);
    $permission = Permission::firstOrCreate(['name' => 'audit.view'], [
        'description' => 'View audit logs',
    ]);
    $role->permissions()->attach($permission);

    $response = $this->getJson('/api/v1/admin/roles', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonFragment([
            'id' => $role->id,
            'slug' => 'support_admin',
        ]);
});

it('creates and updates a role', function (): void {
    $this->asAdmin();

    $create = $this->postJson('/api/v1/admin/roles', [
        'name' => 'Support Admin',
        'slug' => 'support_admin',
        'description' => 'Support role',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.slug', 'support_admin');

    $roleId = $create->json('data.id');

    $update = $this->putJson("/api/v1/admin/roles/{$roleId}", [
        'name' => 'Support Admin Updated',
        'slug' => 'support_admin_updated',
    ], $this->adminHeaders());

    $update->assertOk()
        ->assertJsonPath('data.slug', 'support_admin_updated');
});

it('syncs role permissions', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'content_admin']);
    $permission = Permission::firstOrCreate(['name' => 'course.manage'], [
        'description' => 'Manage courses',
    ]);

    $response = $this->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
        'permission_ids' => [$permission->id],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.permissions.0', 'course.manage');
});

it('deletes a role', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'temp_role']);

    $response = $this->deleteJson("/api/v1/admin/roles/{$role->id}", [], $this->adminHeaders());

    $response->assertStatus(204);
});
