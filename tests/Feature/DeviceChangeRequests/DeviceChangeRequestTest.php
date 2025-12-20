<?php

declare(strict_types=1);

use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('device-change-requests');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);
});

function registerActiveDevice(User $user, string $uuid = 'device-1')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Phone',
        'device_os' => '1.0',
    ]);
}

it('creates a device change request and blocks duplicate pending', function (): void {
    registerActiveDevice($this->apiUser, 'old-device');

    $response = $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'new-device',
        'model' => 'Model X',
        'os_version' => '2.0',
        'reason' => 'Lost device',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_PENDING);

    $duplicate = $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'other-device',
        'model' => 'Model Y',
        'os_version' => '3.0',
    ]);

    $duplicate->assertStatus(422)->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('admin approves and swaps active device', function (): void {
    registerActiveDevice($this->apiUser, 'old-device');

    $request = $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'new-device',
        'model' => 'Model X',
        'os_version' => '2.0',
    ]);
    $requestId = $request->json('data.id');

    $this->asAdmin();

    $approve = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/approve", [], $this->adminHeaders());

    $approve->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_APPROVED);

    $old = UserDevice::where('user_id', $this->apiUser->id)->where('device_id', 'old-device')->first();
    $new = UserDevice::where('user_id', $this->apiUser->id)->where('device_id', 'new-device')->first();

    expect($old?->status)->toBe(UserDevice::STATUS_REVOKED);
    expect($new?->status)->toBe(UserDevice::STATUS_ACTIVE);
});

it('admin can reject pending request without device changes', function (): void {
    registerActiveDevice($this->apiUser, 'old-device');

    $request = $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'new-device',
        'model' => 'Model X',
        'os_version' => '2.0',
    ]);
    $requestId = $request->json('data.id');

    $this->asAdmin();

    $reject = $this->postJson("/api/v1/admin/device-change-requests/{$requestId}/reject", [
        'decision_reason' => 'No proof',
    ], $this->adminHeaders());

    $reject->assertOk()->assertJsonPath('data.status', DeviceChangeRequest::STATUS_REJECTED);

    $active = UserDevice::where('user_id', $this->apiUser->id)->where('status', UserDevice::STATUS_ACTIVE)->first();
    expect($active?->device_id)->toBe('old-device');
});

it('lists only own device change requests', function (): void {
    registerActiveDevice($this->apiUser, 'old-device');

    $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'new-device',
        'model' => 'Model X',
        'os_version' => '2.0',
    ]);

    $other = User::factory()->create(['is_student' => true, 'password' => 'secret123']);
    registerActiveDevice($other, 'other-old');
    $this->asApiUser($other);
    $this->apiPost('/api/v1/device-change-requests', [
        'new_device_id' => 'other-new',
        'model' => 'Model Y',
        'os_version' => '3.0',
    ]);

    $list = $this->apiGet('/api/v1/device-change-requests');
    $list->assertOk()->assertJsonCount(1, 'data');
});
