<?php

declare(strict_types=1);

use App\Models\DeviceChangeRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('device-change-requests', 'admin');

function registerAdminDevice(User $user, string $uuid = 'device-1')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Phone',
        'device_os' => '1.0',
    ]);
}

it('admin approves and swaps active device', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/approve", [], $this->adminHeaders());

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED->value);

    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    $new = UserDevice::where('user_id', $student->id)->where('device_id', 'new-device')->first();

    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
    expect($new?->status)->toBe(UserDevice::STATUS_ACTIVE);
});

it('admin can reject pending request without device changes', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $reject = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/reject", [
        'decision_reason' => 'No proof',
    ], $this->adminHeaders());

    $reject->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_REJECTED->value);

    $active = UserDevice::where('user_id', $student->id)
        ->where('status', UserDevice::STATUS_ACTIVE)
        ->first();
    expect($active?->device_id)->toBe('old-device');
});

it('admin can approve request with provided device details', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/approve", [
        'new_device_id' => 'new-device',
        'new_model' => 'Model Y',
        'new_os_version' => '3.0',
    ], $this->adminHeaders());

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED->value);

    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    $new = UserDevice::where('user_id', $student->id)->where('device_id', 'new-device')->first();

    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
    expect($new?->status)->toBe(UserDevice::STATUS_ACTIVE);
});

it('admin approve falls back to pre-approved flow when request has no target device yet', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/approve", [], $this->adminHeaders());

    $approve->assertOk()
        ->assertJsonPath('message', 'Device change request pre-approved')
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PRE_APPROVED->value);

    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);

    /** @var DeviceServiceInterface $deviceService */
    $deviceService = app(DeviceServiceInterface::class);
    $newDevice = $deviceService->register($student, 'fresh-device', [
        'device_name' => 'Phone Z',
        'device_os' => '5.0',
    ]);

    expect($newDevice->status)->toBe(UserDevice::STATUS_ACTIVE);
    expect($request->fresh()?->status)->toBe(DeviceChangeRequest::STATUS_APPROVED);
    expect($request->fresh()?->new_device_id)->toBe('fresh-device');
});

it('admin can pre-approve pending request', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $preApprove = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/pre-approve", [
        'decision_reason' => 'Student verified via phone call',
    ], $this->adminHeaders());

    $preApprove->assertOk()
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PRE_APPROVED->value)
        ->assertJsonPath('data.decision_reason', 'Student verified via phone call');

    // Old device should be revoked
    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
});

it('admin can only pre-approve pending requests', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_APPROVED,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $preApprove = $this->postJson("/api/v1/admin/centers/{$centerId}/device-change-requests/{$requestId}/pre-approve", [], $this->adminHeaders());

    $preApprove->assertStatus(409)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

it('admin can create device change request for student', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'current-device');
    registerAdminDevice($student, 'current-device');

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/centers/{$centerId}/students/{$student->id}/device-change-requests", [
        'reason' => 'Student lost their phone',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.current_device_id', 'current-device')
        ->assertJsonPath('data.reason', 'Student lost their phone')
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PENDING->value)
        ->assertJsonPath('data.request_source', 'ADMIN');
});

it('admin cannot create device change for non-student', function (): void {
    $admin = $this->asAdmin();
    $centerId = $admin->center_id ?? \App\Models\Center::factory()->create()->id;
    $admin2 = User::factory()->create([
        'is_student' => false,
        'center_id' => $centerId,
    ]);

    $create = $this->postJson("/api/v1/admin/centers/{$centerId}/students/{$admin2->id}/device-change-requests", [
        'reason' => 'Test reason',
    ], $this->adminHeaders());

    $create->assertStatus(422)
        ->assertJsonPath('error.code', 'NOT_STUDENT');
});

it('admin cannot create duplicate pending request for student', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'current-device');
    registerAdminDevice($student, 'current-device');

    // Create existing pending request
    DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'current-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/centers/{$centerId}/students/{$student->id}/device-change-requests", [
        'reason' => 'Another request',
    ], $this->adminHeaders());

    $create->assertStatus(422)
        ->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('admin can create request for student without active device', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    // No device registered

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/centers/{$centerId}/students/{$student->id}/device-change-requests", [
        'reason' => 'New student setup',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.current_device_id', null)
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PENDING->value);
});

it('supports bulk pre-approve in system scope with skipped and failed', function (): void {
    $student = $this->makeApiUser();
    $centerId = $student->center_id;
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $pending = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $alreadyApproved = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $centerId,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_APPROVED,
    ]);

    $this->asAdmin();

    $response = $this->postJson('/api/v1/admin/device-change-requests/bulk-pre-approve', [
        'request_ids' => [$pending->id, $alreadyApproved->id, 999999],
        'decision_reason' => 'Bulk support review',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.counts.total', 3)
        ->assertJsonPath('data.counts.pre_approved', 1)
        ->assertJsonPath('data.counts.skipped', 1)
        ->assertJsonPath('data.counts.failed', 1);

    $this->assertDatabaseHas('device_change_requests', [
        'id' => $pending->id,
        'status' => DeviceChangeRequest::STATUS_PRE_APPROVED,
    ]);
});

it('allows centerless non-super admin with permission to approve in system scope', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'device_change.manage'], [
        'description' => 'Manage device change requests',
    ]);
    $role = Role::firstOrCreate(['slug' => 'device_change_manager'], [
        'name' => 'Device Change Manager',
        'name_translations' => ['en' => 'Device Change Manager'],
        'description_translations' => ['en' => 'Device change management role'],
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

    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    $response = $this->postJson("/api/v1/admin/device-change-requests/{$request->id}/approve", [], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED->value);
});
