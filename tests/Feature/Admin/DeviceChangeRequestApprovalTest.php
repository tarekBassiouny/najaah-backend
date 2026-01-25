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

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED);

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

    $reject->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_REJECTED);

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

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED);

    $old = UserDevice::where('user_id', $student->id)->where('device_id', 'old-device')->first();
    $new = UserDevice::where('user_id', $student->id)->where('device_id', 'new-device')->first();

    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
    expect($new?->status)->toBe(UserDevice::STATUS_ACTIVE);
});
