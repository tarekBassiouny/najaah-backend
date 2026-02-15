<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('admin-users');

function centerScopedAdminUserHeaders(int $centerId): array
{
    $center = Center::query()->findOrFail($centerId);
    $role = Role::query()->where('slug', 'super_admin')->firstOrFail();

    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $admin->roles()->syncWithoutDetaching([$role->id]);
    $admin->centers()->syncWithoutDetaching([
        (int) $center->id => ['type' => 'admin'],
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) Config::get('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
        'X-Api-Key' => $systemKey,
    ];
}

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

it('supports center-scoped admin CRUD via center routes', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);

    $create = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users', [
        'name' => 'Center Admin User',
        'email' => 'center.admin.user@example.com',
        'phone' => '19990000011',
        'password' => 'secret123',
    ], $headers);

    $create->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center_id', (int) $center->id);

    $adminId = (int) $create->json('data.id');

    $update = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$adminId, [
        'name' => 'Center Admin Updated',
    ], $headers);

    $update->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Center Admin Updated')
        ->assertJsonPath('data.center_id', (int) $center->id);

    $list = $this->getJson('/api/v1/admin/centers/'.$center->id.'/users', $headers);
    $list->assertOk()
        ->assertJsonPath('success', true);

    $delete = $this->deleteJson('/api/v1/admin/centers/'.$center->id.'/users/'.$adminId, [], $headers);
    $delete->assertStatus(204);
});

it('blocks center-scoped admin CRUD on other center route', function (): void {
    $this->asAdmin();
    $ownedCenter = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $ownedCenter->id);

    $response = $this->postJson('/api/v1/admin/centers/'.$otherCenter->id.'/users', [
        'name' => 'Blocked Admin User',
        'email' => 'blocked.admin.user@example.com',
        'phone' => '19990000012',
        'password' => 'secret123',
    ], $headers);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('returns not found when center route admin target is outside route center', function (): void {
    $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $targetAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $centerB->id,
        'email' => 'outside.route.center.admin@example.com',
        'phone' => '19990000013',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$centerA->id.'/users/'.$targetAdmin->id, [
        'name' => 'Should Not Update',
    ], $this->adminHeaders());

    $response->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('syncs admin roles via center route for center-scoped super admin', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);

    $role = Role::factory()->create(['slug' => 'center_support_admin']);
    $targetAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'email' => 'center.route.role.admin@example.com',
        'phone' => '19990000014',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$targetAdmin->id.'/roles', [
        'role_ids' => [$role->id],
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.roles.0', 'center_support_admin');
});
