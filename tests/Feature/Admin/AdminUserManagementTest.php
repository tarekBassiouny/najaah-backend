<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('admin-users');

it('denies admin user access without permission', function (): void {
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
    ]);
    $this->adminToken = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $response = $this->getJson('/api/v1/admin/users', $this->adminHeaders());

    $response->assertStatus(403)->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('creates, updates, and deletes admin users', function (): void {
    $this->asAdmin();

    $create = $this->postJson('/api/v1/admin/users', [
        'name' => 'Admin User',
        'email' => 'admin.user@example.com',
        'phone' => '19990000001',
        'password' => 'secret123',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.name', 'Admin User');

    $adminId = $create->json('data.id');

    $update = $this->putJson("/api/v1/admin/users/{$adminId}", [
        'name' => 'Admin User Updated',
    ], $this->adminHeaders());

    $update->assertOk()
        ->assertJsonPath('data.name', 'Admin User Updated');

    $delete = $this->deleteJson("/api/v1/admin/users/{$adminId}", [], $this->adminHeaders());

    $delete->assertStatus(204);
});

it('syncs admin roles', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'support_admin']);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'phone' => '19990000002',
        'email' => 'admin.role@example.com',
    ]);

    $response = $this->putJson("/api/v1/admin/users/{$admin->id}/roles", [
        'role_ids' => [$role->id],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.roles.0', 'support_admin');
});
