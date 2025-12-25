<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\DeviceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class)->group('devices', 'services', 'mobile');

test('register creates new device', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    $service = new DeviceService;
    $device = $service->register($user, 'device-123', [
        'device_name' => 'iPhone',
        'device_os' => 'iOS',
    ]);

    expect($device)->toBeInstanceOf(UserDevice::class);
});

test('register updates existing device', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var UserDevice $existing */
    $existing = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'device-123',
        'model' => 'Old',
        'os_version' => 'old-os',
        'status' => 1,
    ]);

    $service = new DeviceService;
    $device = $service->register($user, 'device-123', [
        'device_name' => 'New Name',
        'device_os' => 'NewOS',
    ]);

    expect($device->is($existing))->toBeTrue();
});
