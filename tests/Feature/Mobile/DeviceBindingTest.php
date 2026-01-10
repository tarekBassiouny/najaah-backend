<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('devices', 'mobile');

it('binds first device as active', function (): void {
    $service = app(DeviceServiceInterface::class);
    $student = User::factory()->create(['is_student' => true]);

    $device = $service->register($student, 'device-1', [
        'device_name' => 'iPhone',
        'device_os' => 'iOS',
    ]);

    expect($device->status)->toBe(UserDevice::STATUS_ACTIVE);
    $this->assertDatabaseHas('user_devices', [
        'user_id' => $student->id,
        'device_id' => 'device-1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);
});

it('allows repeat login from same device', function (): void {
    $service = app(DeviceServiceInterface::class);
    $student = User::factory()->create(['is_student' => true]);

    $first = $service->register($student, 'device-1', [
        'device_name' => 'iPhone',
        'device_os' => 'iOS',
    ]);

    $second = $service->register($student, 'device-1', [
        'device_name' => 'iPhone Pro',
        'device_os' => 'iOS 18',
    ]);

    expect($second->id)->toBe($first->id);
    expect($second->status)->toBe(UserDevice::STATUS_ACTIVE);
});

it('blocks login from a different device when one is active', function (): void {
    $service = app(DeviceServiceInterface::class);
    $student = User::factory()->create(['is_student' => true]);

    $service->register($student, 'device-1', [
        'device_name' => 'iPhone',
        'device_os' => 'iOS',
    ]);

    $this->expectException(DomainException::class);

    $service->register($student, 'device-2', [
        'device_name' => 'Android',
        'device_os' => '13',
    ]);
});
