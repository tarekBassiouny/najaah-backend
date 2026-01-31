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
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'slug', 'name_translations', 'permissions'],
            ],
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonFragment([
            'id' => $role->id,
            'slug' => 'support_admin',
        ]);
});

it('shows a single role with permissions', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create([
        'slug' => 'content_manager',
        'name_translations' => ['en' => 'Content Manager', 'ar' => 'مدير المحتوى'],
    ]);
    $permission = Permission::firstOrCreate(['name' => 'course.manage'], [
        'description' => 'Manage courses',
    ]);
    $role->permissions()->attach($permission);

    $response = $this->getJson("/api/v1/admin/roles/{$role->id}", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $role->id)
        ->assertJsonPath('data.slug', 'content_manager')
        ->assertJsonPath('data.name_translations.en', 'Content Manager')
        ->assertJsonPath('data.permissions.0', 'course.manage');
});

it('creates a role with translations', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/roles', [
        'name_translations' => [
            'en' => 'Support Admin',
            'ar' => 'مدير الدعم',
        ],
        'slug' => 'support_admin',
        'description_translations' => [
            'en' => 'Support role',
            'ar' => 'دور الدعم',
        ],
    ], $this->adminHeaders());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'support_admin')
        ->assertJsonPath('data.name_translations.en', 'Support Admin')
        ->assertJsonPath('data.name_translations.ar', 'مدير الدعم');
});

it('updates a role with translations', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'support_admin']);

    $response = $this->putJson("/api/v1/admin/roles/{$role->id}", [
        'name_translations' => [
            'en' => 'Support Admin Updated',
            'ar' => 'مدير الدعم المحدث',
        ],
        'slug' => 'support_admin_updated',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'support_admin_updated')
        ->assertJsonPath('data.name_translations.en', 'Support Admin Updated');
});

it('validates required fields on create', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/roles', [
        'description_translations' => ['en' => 'Some description'],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.name_translations.0', 'The name translations field is required.')
        ->assertJsonPath('error.details.slug.0', 'The slug field is required.');
});

it('validates unique slug on create', function (): void {
    $this->asAdmin();
    Role::factory()->create(['slug' => 'existing_role']);

    $response = $this->postJson('/api/v1/admin/roles', [
        'name_translations' => ['en' => 'Test Role'],
        'slug' => 'existing_role',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
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

it('allows removing all permissions from a role', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'content_admin']);
    $permission = Permission::firstOrCreate(['name' => 'course.manage'], [
        'description' => 'Manage courses',
    ]);
    $role->permissions()->attach($permission);

    $response = $this->putJson("/api/v1/admin/roles/{$role->id}/permissions", [
        'permission_ids' => [],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.permissions', []);
});

it('deletes a role', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'temp_role']);

    $response = $this->deleteJson("/api/v1/admin/roles/{$role->id}", [], $this->adminHeaders());

    $response->assertStatus(204);

    $this->assertSoftDeleted('roles', ['id' => $role->id]);
});

it('returns 404 for non-existent role', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/roles/99999', $this->adminHeaders());

    $response->assertNotFound();
});
