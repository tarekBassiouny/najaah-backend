<?php

declare(strict_types=1);

use App\Enums\DeviceChangeRequestStatus;
use App\Enums\UserDeviceStatus;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\DeviceChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('devices', 'services');

it('creates admin-initiated request even when student has no active device', function (): void {
    $admin = $this->asAdmin();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $service = app(DeviceChangeService::class);
    $request = $service->createByAdmin($admin, $student, 'Lost device');

    expect($request->status)->toBe(DeviceChangeRequestStatus::Pending);
    expect($request->request_source?->value)->toBe('ADMIN');
    expect($request->current_device_id)->toBeNull();
});

it('approves pending request and revokes old device', function (): void {
    $admin = $this->asAdmin();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $old = UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'old-device',
        'status' => UserDeviceStatus::Active,
    ]);

    $request = DeviceChangeRequest::factory()->create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'new_device_id' => 'new-device',
        'new_model' => 'Model Y',
        'new_os_version' => '2.0',
        'status' => DeviceChangeRequestStatus::Pending,
    ]);

    $service = app(DeviceChangeService::class);
    $approved = $service->approve($admin, $request);

    expect($approved->status)->toBe(DeviceChangeRequestStatus::Approved);
    expect(UserDevice::where('id', $old->id)->value('status'))->toBe(UserDeviceStatus::Revoked);
    expect(UserDevice::where('user_id', $student->id)->where('device_id', 'new-device')->value('status'))
        ->toBe(UserDeviceStatus::Active);
});

it('pre-approves then completes device swap from login', function (): void {
    $admin = $this->asAdmin();
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    UserDevice::factory()->create([
        'user_id' => $student->id,
        'device_id' => 'old-device',
        'status' => UserDeviceStatus::Active,
    ]);

    $request = DeviceChangeRequest::factory()->create([
        'user_id' => $student->id,
        'center_id' => $student->center_id,
        'current_device_id' => 'old-device',
        'status' => DeviceChangeRequestStatus::Pending,
    ]);

    $service = app(DeviceChangeService::class);
    $preApproved = $service->preApprove($admin, $request, 'Verified');
    expect($preApproved->status)->toBe(DeviceChangeRequestStatus::PreApproved);

    $device = $service->completePreApproved($request->fresh(), 'fresh-device', 'Model Z', '3.0');
    expect($device->status)->toBe(UserDeviceStatus::Active);
    expect($request->fresh()?->status)->toBe(DeviceChangeRequestStatus::Approved);
});
