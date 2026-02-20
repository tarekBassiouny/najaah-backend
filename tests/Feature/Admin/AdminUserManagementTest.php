<?php

declare(strict_types=1);

use App\Jobs\SendAdminPasswordResetEmailJob;
use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
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
    Bus::fake();
    $this->asAdmin();

    $create = $this->postJson('/api/v1/admin/users', [
        'name' => 'Admin User',
        'email' => 'admin.user@example.com',
        'phone' => '1999000001',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.name', 'Admin User');

    $adminId = $create->json('data.id');
    $this->assertDatabaseHas('users', [
        'id' => (int) $adminId,
        'force_password_reset' => true,
    ]);
    Bus::assertDispatched(
        SendAdminPasswordResetEmailJob::class,
        fn (SendAdminPasswordResetEmailJob $job): bool => $job->userId === (int) $adminId && $job->isInvite
    );

    $update = $this->putJson("/api/v1/admin/users/{$adminId}", [
        'name' => 'Admin User Updated',
    ], $this->adminHeaders());

    $update->assertOk()
        ->assertJsonPath('data.name', 'Admin User Updated');

    $delete = $this->deleteJson("/api/v1/admin/users/{$adminId}", [], $this->adminHeaders());

    $delete->assertStatus(204);
});

it('allows admin and student to share phone in the same center', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '1999777001',
        'email' => 'student.same.phone@example.com',
    ]);

    $create = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users', [
        'name' => 'Center Admin Shared Phone',
        'email' => 'center.admin.shared.phone@example.com',
        'phone' => '1999777001',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.phone', '1999777001');
});

it('keeps admin phone unique within admin scope', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'phone' => '1999777002',
        'email' => 'existing.center.admin.phone@example.com',
    ]);

    $response = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users', [
        'name' => 'Duplicate Phone Admin',
        'email' => 'duplicate.center.admin.phone@example.com',
        'phone' => '1999777002',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['phone']]]);
});

it('keeps admin email globally unique across centers', function (): void {
    $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    User::factory()->create([
        'is_student' => false,
        'center_id' => $centerA->id,
        'email' => 'global.admin.email@example.com',
        'phone' => '1999777003',
    ]);

    $response = $this->postJson('/api/v1/admin/centers/'.$centerB->id.'/users', [
        'name' => 'Duplicate Global Email Admin',
        'email' => 'global.admin.email@example.com',
        'phone' => '1999777004',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['email']]]);
});

it('enforces invite-only admin creation by rejecting password field', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/users', [
        'name' => 'Admin User',
        'email' => 'admin.user.invite.only@example.com',
        'phone' => '1999000019',
        'country_code' => '+20',
        'password' => 'secret123',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['password']]]);
});

it('rejects password field on system admin update', function (): void {
    $this->asAdmin();
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'email' => 'admin.user.update.password.blocked@example.com',
        'phone' => '19990000138',
    ]);
    $originalPasswordHash = (string) $admin->password;

    $response = $this->putJson('/api/v1/admin/users/'.$admin->id, [
        'password' => 'newsecret123',
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['password']]]);

    $this->assertSame(
        $originalPasswordHash,
        (string) User::query()->findOrFail((int) $admin->id)->password
    );
});

it('allows updating admin phone to a phone used by student in the same center', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
        'phone' => '1999777005',
        'email' => 'student.target.phone@example.com',
    ]);

    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'phone' => '1999777006',
        'email' => 'admin.update.phone.target@example.com',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$admin->id, [
        'phone' => '1999777005',
        'country_code' => '+20',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.phone', '1999777005');
});

it('rejects password field on center admin update', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
        'email' => 'center.admin.update.password.blocked@example.com',
        'phone' => '19990000139',
    ]);
    $originalPasswordHash = (string) $admin->password;

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$admin->id, [
        'password' => 'newsecret123',
    ], $headers);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['password']]]);

    $this->assertSame(
        $originalPasswordHash,
        (string) User::query()->findOrFail((int) $admin->id)->password
    );
});

it('syncs admin roles', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'support_admin']);
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'phone' => '1999000002',
        'email' => 'admin.role@example.com',
    ]);

    $response = $this->putJson("/api/v1/admin/users/{$admin->id}/roles", [
        'role_ids' => [$role->id],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.roles.0', 'support_admin');
});

it('updates admin status via system status endpoint', function (): void {
    $this->asAdmin();
    $admin = User::factory()->create([
        'is_student' => false,
        'status' => 1,
        'email' => 'status.single.system@example.com',
        'phone' => '19990000131',
    ]);

    $response = $this->putJson('/api/v1/admin/users/'.$admin->id.'/status', [
        'status' => 2,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 2);

    $this->assertDatabaseHas('users', [
        'id' => (int) $admin->id,
        'status' => 2,
    ]);
});

it('bulk updates admin statuses in system scope', function (): void {
    $this->asAdmin();

    $updatableAdmin = User::factory()->create([
        'is_student' => false,
        'status' => 1,
        'email' => 'status.bulk.system.update@example.com',
        'phone' => '19990000132',
    ]);
    $alreadyStatusAdmin = User::factory()->create([
        'is_student' => false,
        'status' => 2,
        'email' => 'status.bulk.system.skip@example.com',
        'phone' => '19990000133',
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'status' => 1,
        'email' => 'status.bulk.system.student@example.com',
        'phone' => '19990000134',
    ]);

    $response = $this->postJson('/api/v1/admin/users/bulk-status', [
        'status' => 2,
        'user_ids' => [(int) $updatableAdmin->id, (int) $alreadyStatusAdmin->id, (int) $student->id, 999999],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 2)
        ->assertJsonPath('data.skipped.0.user_id', (int) $alreadyStatusAdmin->id)
        ->assertJsonPath('data.failed.0.user_id', (int) $student->id)
        ->assertJsonPath('data.failed.1.user_id', 999999);

    $this->assertDatabaseHas('users', [
        'id' => (int) $updatableAdmin->id,
        'status' => 2,
    ]);
});

it('bulk syncs admin roles in system scope', function (): void {
    $this->asAdmin();
    $role = Role::factory()->create(['slug' => 'bulk_system_support_admin']);
    $otherRole = Role::factory()->create(['slug' => 'bulk_system_other_role']);

    $updatableAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'bulk.system.role.update@example.com',
        'phone' => '19990000121',
    ]);
    $alreadyAssignedAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'bulk.system.role.skip@example.com',
        'phone' => '19990000122',
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'email' => 'bulk.system.role.student@example.com',
        'phone' => '19990000123',
    ]);

    $updatableAdmin->roles()->sync([$otherRole->id]);
    $alreadyAssignedAdmin->roles()->sync([$role->id]);

    $response = $this->postJson('/api/v1/admin/users/roles/bulk', [
        'user_ids' => [(int) $updatableAdmin->id, (int) $alreadyAssignedAdmin->id, (int) $student->id, 999999],
        'role_ids' => [(int) $role->id],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 2)
        ->assertJsonPath('data.skipped.0.user_id', (int) $alreadyAssignedAdmin->id)
        ->assertJsonPath('data.failed.0.user_id', (int) $student->id)
        ->assertJsonPath('data.failed.1.user_id', 999999);

    $this->assertDatabaseHas('role_user', [
        'user_id' => (int) $updatableAdmin->id,
        'role_id' => (int) $role->id,
    ]);
});

it('filters admin users by email search and role id', function (): void {
    $this->asAdmin();
    $targetRole = Role::factory()->create([
        'slug' => 'admin_filter_target',
        'name' => 'Admin Filter Target',
    ]);
    $otherRole = Role::factory()->create([
        'slug' => 'admin_filter_other',
        'name' => 'Admin Filter Other',
    ]);

    $matchingAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'filter.me@example.com',
        'phone' => '19997770101',
    ]);
    $wrongRoleAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'filter.me.other@example.com',
        'phone' => '19997770102',
    ]);
    $wrongSearchAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'another.admin@example.com',
        'phone' => '19997770103',
    ]);

    $matchingAdmin->roles()->sync([$targetRole->id]);
    $wrongRoleAdmin->roles()->sync([$otherRole->id]);
    $wrongSearchAdmin->roles()->sync([$targetRole->id]);

    $response = $this->getJson('/api/v1/admin/users?page=1&per_page=10&search=filter.me&role_id='.$targetRole->id, $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $matchingAdmin->id)
        ->not->toContain((int) $wrongRoleAdmin->id)
        ->not->toContain((int) $wrongSearchAdmin->id);
});

it('searches admin users by phone', function (): void {
    $this->asAdmin();

    $matchingAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'phone.search.admin@example.com',
        'phone' => '19998887766',
    ]);
    $otherAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'phone.search.other@example.com',
        'phone' => '19990001122',
    ]);

    $response = $this->getJson('/api/v1/admin/users?page=1&per_page=10&search=19998887766', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $matchingAdmin->id)
        ->not->toContain((int) $otherAdmin->id);
});

it('filters admin users by status', function (): void {
    $this->asAdmin();

    $activeAdmin = User::factory()->create([
        'is_student' => false,
        'status' => 1,
        'email' => 'status.filter.active@example.com',
        'phone' => '19998880001',
    ]);
    $inactiveAdmin = User::factory()->create([
        'is_student' => false,
        'status' => 0,
        'email' => 'status.filter.inactive@example.com',
        'phone' => '19998880002',
    ]);
    $bannedAdmin = User::factory()->create([
        'is_student' => false,
        'status' => 2,
        'email' => 'status.filter.banned@example.com',
        'phone' => '19998880003',
    ]);

    $response = $this->getJson('/api/v1/admin/users?page=1&per_page=10&status=1', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect((array) $response->json('data'))
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

    expect($ids)
        ->toContain((int) $activeAdmin->id)
        ->not->toContain((int) $inactiveAdmin->id)
        ->not->toContain((int) $bannedAdmin->id);
});

it('supports center-scoped admin CRUD via center routes', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);

    $create = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users', [
        'name' => 'Center Admin User',
        'email' => 'center.admin.user@example.com',
        'phone' => '1999000011',
        'country_code' => '+20',
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
        'phone' => '1999000012',
        'country_code' => '+20',
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
        'phone' => '1999000013',
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
        'phone' => '1999000014',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$targetAdmin->id.'/roles', [
        'role_ids' => [$role->id],
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.roles.0', 'center_support_admin');
});

it('updates admin status via center status endpoint for center-scoped super admin', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);

    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'status' => 1,
        'email' => 'status.single.center@example.com',
        'phone' => '19990000135',
    ]);

    $response = $this->putJson('/api/v1/admin/centers/'.$center->id.'/users/'.$admin->id.'/status', [
        'status' => 0,
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 0);

    $this->assertDatabaseHas('users', [
        'id' => (int) $admin->id,
        'status' => 0,
    ]);
});

it('bulk updates admin statuses via center route for center-scoped super admin', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);

    $updatableAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'status' => 1,
        'email' => 'status.bulk.center.update@example.com',
        'phone' => '19990000136',
    ]);
    $outsideAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $otherCenter->id,
        'status' => 1,
        'email' => 'status.bulk.center.outside@example.com',
        'phone' => '19990000137',
    ]);

    $response = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users/bulk-status', [
        'status' => 2,
        'user_ids' => [(int) $updatableAdmin->id, (int) $outsideAdmin->id],
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.failed', 1)
        ->assertJsonPath('data.failed.0.user_id', (int) $outsideAdmin->id);

    $this->assertDatabaseHas('users', [
        'id' => (int) $updatableAdmin->id,
        'status' => 2,
    ]);
});

it('bulk syncs admin roles via center route for center-scoped super admin', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $headers = centerScopedAdminUserHeaders((int) $center->id);
    $role = Role::factory()->create(['slug' => 'center_bulk_support_admin']);

    $updatableAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $center->id,
        'email' => 'center.bulk.role.update@example.com',
        'phone' => '19990000124',
    ]);
    $outsideAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => $otherCenter->id,
        'email' => 'center.bulk.role.outside@example.com',
        'phone' => '19990000125',
    ]);

    $response = $this->postJson('/api/v1/admin/centers/'.$center->id.'/users/roles/bulk', [
        'user_ids' => [(int) $updatableAdmin->id, (int) $outsideAdmin->id],
        'role_ids' => [(int) $role->id],
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 2)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.failed', 1)
        ->assertJsonPath('data.failed.0.user_id', (int) $outsideAdmin->id);

    $this->assertDatabaseHas('role_user', [
        'user_id' => (int) $updatableAdmin->id,
        'role_id' => (int) $role->id,
    ]);
});

it('assigns an admin user to a center via system route', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'assign.single.admin@example.com',
        'phone' => '1999000015',
    ]);

    $response = $this->putJson('/api/v1/admin/users/'.$admin->id.'/assign-center', [
        'center_id' => (int) $center->id,
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.center_id', (int) $center->id);

    $this->assertDatabaseHas('users', [
        'id' => (int) $admin->id,
        'center_id' => (int) $center->id,
    ]);
    $this->assertDatabaseHas('user_centers', [
        'user_id' => (int) $admin->id,
        'center_id' => (int) $center->id,
        'type' => 'admin',
    ]);
});

it('bulk assigns admin users to centers and reports updated skipped and failed', function (): void {
    $this->asAdmin();
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $updatableAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'bulk.assign.updatable@example.com',
        'phone' => '1999000016',
    ]);
    $alreadyAssignedAdmin = User::factory()->create([
        'is_student' => false,
        'center_id' => (int) $centerA->id,
        'email' => 'bulk.assign.already@example.com',
        'phone' => '1999000017',
    ]);
    $student = User::factory()->create([
        'is_student' => true,
        'email' => 'bulk.assign.student@example.com',
        'phone' => '1999000018',
    ]);

    $response = $this->postJson('/api/v1/admin/users/assign-center/bulk', [
        'assignments' => [
            ['user_id' => (int) $updatableAdmin->id, 'center_id' => (int) $centerB->id],
            ['user_id' => (int) $alreadyAssignedAdmin->id, 'center_id' => (int) $centerA->id],
            ['user_id' => (int) $student->id, 'center_id' => (int) $centerB->id],
            ['user_id' => 999999, 'center_id' => (int) $centerB->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 4)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 2)
        ->assertJsonPath('data.skipped.0.user_id', (int) $alreadyAssignedAdmin->id)
        ->assertJsonPath('data.failed.0.user_id', (int) $student->id)
        ->assertJsonPath('data.failed.1.user_id', 999999);

    $this->assertDatabaseHas('users', [
        'id' => (int) $updatableAdmin->id,
        'center_id' => (int) $centerB->id,
    ]);
    $this->assertDatabaseHas('user_centers', [
        'user_id' => (int) $updatableAdmin->id,
        'center_id' => (int) $centerB->id,
        'type' => 'admin',
    ]);
});

it('supports bulk assign centers legacy route alias', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();
    $admin = User::factory()->create([
        'is_student' => false,
        'center_id' => null,
        'email' => 'bulk.assign.legacy.alias@example.com',
        'phone' => '19990000140',
    ]);

    $response = $this->putJson('/api/v1/admin/users/bulk-assign-centers', [
        'assignments' => [
            ['user_id' => (int) $admin->id, 'center_id' => (int) $center->id],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.counts.total', 1)
        ->assertJsonPath('data.counts.updated', 1)
        ->assertJsonPath('data.counts.skipped', 0)
        ->assertJsonPath('data.counts.failed', 0);

    $this->assertDatabaseHas('users', [
        'id' => (int) $admin->id,
        'center_id' => (int) $center->id,
    ]);
});
