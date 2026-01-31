<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\DeviceChangeRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('device-change-requests', 'admin', 'security');

function createAdminForCenter(Center $center): User
{
    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $role = Role::firstOrCreate(['slug' => 'center_admin'], [
        'name' => 'center admin',
        'name_translations' => ['en' => 'center admin', 'ar' => 'مدير المركز'],
        'description_translations' => ['en' => 'Center administrator', 'ar' => 'مدير المركز'],
    ]);

    $permission = Permission::firstOrCreate(['name' => 'device_change.manage'], [
        'description' => 'Manage device change requests',
    ]);
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $admin->roles()->syncWithoutDetaching([$role->id]);

    return $admin;
}

function getAdminToken(User $admin): string
{
    return (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);
}

function adminHeadersFor(string $token): array
{
    $systemKey = (string) Config::get('services.system_api_key', 'system-test-key');
    Config::set('services.system_api_key', $systemKey);

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
        'X-Api-Key' => $systemKey,
    ];
}

function registerDevice(User $user, string $deviceId): UserDevice
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $deviceId, [
        'device_name' => 'Test Phone',
        'device_os' => '1.0',
    ]);
}

// =============================================================================
// CROSS-CENTER AUTHORIZATION TESTS
// =============================================================================

it('admin cannot approve device change request from another center', function (): void {
    // Center A setup
    $centerA = Center::factory()->create(['name_translations' => ['en' => 'Center A']]);
    $adminA = createAdminForCenter($centerA);
    $tokenA = getAdminToken($adminA);

    // Center B setup with request
    $centerB = Center::factory()->create(['name_translations' => ['en' => 'Center B']]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);
    registerDevice($studentB, 'device-b');

    $requestB = DeviceChangeRequest::create([
        'user_id' => $studentB->id,
        'center_id' => $centerB->id,
        'current_device_id' => 'device-b',
        'new_device_id' => 'new-device-b',
        'new_model' => 'Model B',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    // Admin A tries to approve Center B's request
    $response = test()->postJson(
        "/api/v1/admin/device-change-requests/{$requestB->id}/approve",
        [],
        adminHeadersFor($tokenA)
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('admin cannot reject device change request from another center', function (): void {
    // Center A setup
    $centerA = Center::factory()->create(['name_translations' => ['en' => 'Center A']]);
    $adminA = createAdminForCenter($centerA);
    $tokenA = getAdminToken($adminA);

    // Center B setup with request
    $centerB = Center::factory()->create(['name_translations' => ['en' => 'Center B']]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);
    registerDevice($studentB, 'device-b');

    $requestB = DeviceChangeRequest::create([
        'user_id' => $studentB->id,
        'center_id' => $centerB->id,
        'current_device_id' => 'device-b',
        'new_device_id' => 'new-device-b',
        'new_model' => 'Model B',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    // Admin A tries to reject Center B's request
    $response = test()->postJson(
        "/api/v1/admin/device-change-requests/{$requestB->id}/reject",
        ['decision_reason' => 'Unauthorized attempt'],
        adminHeadersFor($tokenA)
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('admin cannot pre-approve device change request from another center', function (): void {
    // Center A setup
    $centerA = Center::factory()->create(['name_translations' => ['en' => 'Center A']]);
    $adminA = createAdminForCenter($centerA);
    $tokenA = getAdminToken($adminA);

    // Center B setup with request
    $centerB = Center::factory()->create(['name_translations' => ['en' => 'Center B']]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);
    registerDevice($studentB, 'device-b');

    $requestB = DeviceChangeRequest::create([
        'user_id' => $studentB->id,
        'center_id' => $centerB->id,
        'current_device_id' => 'device-b',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    // Admin A tries to pre-approve Center B's request
    $response = test()->postJson(
        "/api/v1/admin/device-change-requests/{$requestB->id}/pre-approve",
        [],
        adminHeadersFor($tokenA)
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('admin cannot create device change request for student in another center', function (): void {
    // Center A setup
    $centerA = Center::factory()->create(['name_translations' => ['en' => 'Center A']]);
    $adminA = createAdminForCenter($centerA);
    $tokenA = getAdminToken($adminA);

    // Center B student
    $centerB = Center::factory()->create(['name_translations' => ['en' => 'Center B']]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);

    // Admin A tries to create request for Center B's student
    $response = test()->postJson(
        "/api/v1/admin/students/{$studentB->id}/device-change-requests",
        ['reason' => 'Cross-center attempt'],
        adminHeadersFor($tokenA)
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

// =============================================================================
// LIST ENDPOINT CENTER SCOPING TESTS
// =============================================================================

it('admin only sees device change requests from their center', function (): void {
    // Center A setup
    $centerA = Center::factory()->create(['name_translations' => ['en' => 'Center A']]);
    $adminA = createAdminForCenter($centerA);
    $tokenA = getAdminToken($adminA);
    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    registerDevice($studentA, 'device-a');

    DeviceChangeRequest::create([
        'user_id' => $studentA->id,
        'center_id' => $centerA->id,
        'current_device_id' => 'device-a',
        'new_device_id' => 'new-device-a',
        'new_model' => 'Model A',
        'new_os_version' => '1.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    // Center B setup
    $centerB = Center::factory()->create(['name_translations' => ['en' => 'Center B']]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);
    registerDevice($studentB, 'device-b');

    DeviceChangeRequest::create([
        'user_id' => $studentB->id,
        'center_id' => $centerB->id,
        'current_device_id' => 'device-b',
        'new_device_id' => 'new-device-b',
        'new_model' => 'Model B',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    // Admin A should only see Center A's requests
    $response = test()->getJson(
        '/api/v1/admin/device-change-requests',
        adminHeadersFor($tokenA)
    );

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.center_id', $centerA->id);
});

// =============================================================================
// STATUS VALIDATION TESTS
// =============================================================================

it('admin cannot approve already approved request', function (): void {
    $center = Center::factory()->create();
    $admin = createAdminForCenter($center);
    $token = getAdminToken($admin);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    registerDevice($student, 'device-1');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $center->id,
        'current_device_id' => 'device-1',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_APPROVED,
    ]);

    $response = test()->postJson(
        "/api/v1/admin/device-change-requests/{$request->id}/approve",
        [],
        adminHeadersFor($token)
    );

    $response->assertStatus(409)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

it('admin cannot reject already rejected request', function (): void {
    $center = Center::factory()->create();
    $admin = createAdminForCenter($center);
    $token = getAdminToken($admin);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    registerDevice($student, 'device-1');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $center->id,
        'current_device_id' => 'device-1',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_REJECTED,
    ]);

    $response = test()->postJson(
        "/api/v1/admin/device-change-requests/{$request->id}/reject",
        ['decision_reason' => 'Double rejection attempt'],
        adminHeadersFor($token)
    );

    $response->assertStatus(409)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

// =============================================================================
// AUTHORIZATION WITHOUT PERMISSION TESTS
// =============================================================================

it('admin without device_change.manage permission cannot access endpoints', function (): void {
    $center = Center::factory()->create();

    // Create admin WITHOUT device_change.manage permission
    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $role = Role::firstOrCreate(['slug' => 'limited_admin'], [
        'name' => 'limited admin',
        'name_translations' => ['en' => 'limited admin', 'ar' => 'مدير محدود'],
        'description_translations' => ['en' => 'Limited administrator', 'ar' => 'مدير محدود'],
    ]);
    // No device_change.manage permission
    $admin->roles()->syncWithoutDetaching([$role->id]);

    $token = getAdminToken($admin);

    // Try to list device change requests
    $response = test()->getJson(
        '/api/v1/admin/device-change-requests',
        adminHeadersFor($token)
    );

    $response->assertStatus(403);
});

// =============================================================================
// STUDENT VALIDATION TESTS
// =============================================================================

it('rejects device change request creation for non-existent student', function (): void {
    $center = Center::factory()->create();
    $admin = createAdminForCenter($center);
    $token = getAdminToken($admin);

    $response = test()->postJson(
        '/api/v1/admin/students/999999/device-change-requests',
        ['reason' => 'Test'],
        adminHeadersFor($token)
    );

    $response->assertStatus(404);
});
