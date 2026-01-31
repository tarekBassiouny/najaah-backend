<?php

declare(strict_types=1);

use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    $requestId = $request->id;

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/approve", [], $this->adminHeaders());

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED->value);

    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    $new = UserDevice::where('user_id', $student->id)->where('device_id', 'new-device')->first();

    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
    expect($new?->status)->toBe(UserDevice::STATUS_ACTIVE);
});

it('admin can reject pending request without device changes', function (): void {
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
    $requestId = $request->id;

    $this->asAdmin();

    $reject = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/reject", [
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
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/approve", [
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

it('admin can pre-approve pending request', function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $preApprove = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/pre-approve", [
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
    $this->asApiUser($student, null, 'old-device');
    registerAdminDevice($student, 'old-device');

    $request = DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model X',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequest::STATUS_APPROVED,
    ]);
    $requestId = $request->id;

    $this->asAdmin();

    $preApprove = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/pre-approve", [], $this->adminHeaders());

    $preApprove->assertStatus(409)
        ->assertJsonPath('error.code', 'INVALID_STATE');
});

it('admin can create device change request for student', function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'current-device');
    registerAdminDevice($student, 'current-device');

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/students/{$student->id}/device-change-requests", [
        'reason' => 'Student lost their phone',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.current_device_id', 'current-device')
        ->assertJsonPath('data.reason', 'Student lost their phone')
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PENDING->value)
        ->assertJsonPath('data.request_source', 'ADMIN');
});

it('admin cannot create device change for non-student', function (): void {
    $admin2 = User::factory()->create([
        'is_student' => false,
        'center_id' => $this->asAdmin()->center_id,
    ]);

    $create = $this->postJson("/api/v1/admin/students/{$admin2->id}/device-change-requests", [
        'reason' => 'Test reason',
    ], $this->adminHeaders());

    $create->assertStatus(422)
        ->assertJsonPath('error.code', 'NOT_STUDENT');
});

it('admin cannot create duplicate pending request for student', function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'current-device');
    registerAdminDevice($student, 'current-device');

    // Create existing pending request
    DeviceChangeRequest::create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'current-device',
        'new_device_id' => '',
        'new_model' => '',
        'new_os_version' => '',
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/students/{$student->id}/device-change-requests", [
        'reason' => 'Another request',
    ], $this->adminHeaders());

    $create->assertStatus(422)
        ->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('admin can create request for student without active device', function (): void {
    $student = $this->makeApiUser();
    // No device registered

    $this->asAdmin();

    $create = $this->postJson("/api/v1/admin/students/{$student->id}/device-change-requests", [
        'reason' => 'New student setup',
    ], $this->adminHeaders());

    $create->assertCreated()
        ->assertJsonPath('data.current_device_id', null)
        ->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PENDING->value);
});
