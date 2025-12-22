<?php

declare(strict_types=1);

use App\Models\DeviceChangeRequest;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('device-change-requests', 'mobile');

beforeEach(function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student, null, 'old-device');
});

function registerMobileDevice(User $user, string $uuid = 'device-1')
{
    /** @var DeviceServiceInterface $service */
    $service = app(DeviceServiceInterface::class);

    return $service->register($user, $uuid, [
        'device_name' => 'Phone',
        'device_os' => '1.0',
    ]);
}

it('creates a device change request', function (): void {
    $device = registerMobileDevice($this->apiUser, 'old-device');

    $response = $this->apiPost('/api/v1/settings/device-change', [
        'reason' => 'Lost device',
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('device_change_requests', [
        'user_id' => $this->apiUser->id,
        'current_device_id' => $device->device_id,
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);
});

it('blocks device change request without active device', function (): void {
    UserDevice::where('user_id', $this->apiUser->id)->update(['status' => UserDevice::STATUS_REVOKED]);
    JwtToken::where('user_id', $this->apiUser->id)->update(['device_id' => null]);

    $response = $this->apiPost('/api/v1/settings/device-change');

    $response->assertStatus(422)->assertJsonPath('error.code', 'NO_ACTIVE_DEVICE');
});

it('blocks duplicate pending device change requests', function (): void {
    $device = registerMobileDevice($this->apiUser, 'old-device');

    DeviceChangeRequest::create([
        'user_id' => $this->apiUser->id,
        'center_id' => $this->apiUser->center_id,
        'current_device_id' => $device->device_id,
        'new_device_id' => $device->device_id,
        'new_model' => $device->model,
        'new_os_version' => $device->os_version,
        'status' => DeviceChangeRequest::STATUS_PENDING,
    ]);

    $response = $this->apiPost('/api/v1/settings/device-change');

    $response->assertStatus(422)->assertJsonPath('error.code', 'PENDING_REQUEST_EXISTS');
});

it('rejects non-student users', function (): void {
    $admin = User::factory()->create(['is_student' => false, 'password' => 'secret123']);
    $this->asApiUser($admin);
    JwtToken::where('user_id', $admin->id)->update(['device_id' => null]);

    $response = $this->apiPost('/api/v1/settings/device-change');

    $response->assertStatus(403)->assertJsonPath('error.code', 'UNAUTHORIZED');
});
