<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\DeviceService;
use App\Support\AuditActions;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class)->group('devices', 'services', 'mobile');

test('register creates new device', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    $service = app(DeviceService::class);
    $device = $service->register($user, 'device-123', [
        'device_name' => 'iPhone',
        'device_type' => 'iPhone 14',
        'device_os' => 'iOS',
    ]);

    expect($device)->toBeInstanceOf(UserDevice::class)
        ->and($device->device_name)->toBe('iPhone')
        ->and($device->device_type)->toBe('iPhone 14')
        ->and($device->model)->toBe('iPhone 14');
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

    $service = app(DeviceService::class);
    $device = $service->register($user, 'device-123', [
        'device_name' => 'New Name',
        'device_type' => 'New Type',
        'device_os' => 'NewOS',
    ]);

    expect($device->is($existing))->toBeTrue()
        ->and($device->device_name)->toBe('New Name')
        ->and($device->device_type)->toBe('New Type')
        ->and($device->model)->toBe('New Type');
});

test('handleReinstall detects same device with different uuid', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var UserDevice $existing */
    $existing = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'old-device-uuid',
        'model' => 'iPhone 14 Pro',
        'os_version' => 'iOS 17.1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $service = app(DeviceService::class);
    $result = $service->handleReinstall($user, 'new-device-uuid', 'iPhone 14 Pro', 'iOS 17.1');

    expect($result)->not->toBeNull()
        ->and($result?->id)->toBe($existing->id)
        ->and($result?->device_id)->toBe('new-device-uuid')
        ->and($result?->os_version)->toBe('iOS 17.1');

    $auditLog = AuditLog::where('action', AuditActions::DEVICE_UUID_UPDATED)
        ->where('entity_type', UserDevice::class)
        ->where('entity_id', $existing->id)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->metadata['old_device_id'])->toBe('old-device-uuid')
        ->and($auditLog?->metadata['new_device_id'])->toBe('new-device-uuid')
        ->and($auditLog?->metadata['reason'])->toBe('reinstall_detected');
});

test('handleReinstall returns null when no matching fingerprint', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var UserDevice $existing */
    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'old-device-uuid',
        'model' => 'iPhone 14 Pro',
        'os_version' => 'iOS 17.1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $service = app(DeviceService::class);
    $result = $service->handleReinstall($user, 'new-device-uuid', 'Samsung Galaxy S24', 'Android 14');

    expect($result)->toBeNull();
});

test('handleReinstall returns null when same device uuid', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var UserDevice $existing */
    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'same-device-uuid',
        'model' => 'iPhone 14 Pro',
        'os_version' => 'iOS 17.1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $service = app(DeviceService::class);
    $result = $service->handleReinstall($user, 'same-device-uuid', 'iPhone 14 Pro', 'iOS 17.1');

    expect($result)->toBeNull();
});

test('register uses handleReinstall for app reinstall scenario', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var UserDevice $existing */
    $existing = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'old-uuid-before-reinstall',
        'model' => 'iPhone 14 Pro',
        'os_version' => 'iOS 17.1',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    $service = app(DeviceService::class);
    $device = $service->register($user, 'new-uuid-after-reinstall', [
        'device_type' => 'iPhone 14 Pro',
        'device_os' => 'iOS 17.1',
    ]);

    expect($device->id)->toBe($existing->id)
        ->and($device->device_id)->toBe('new-uuid-after-reinstall');
});
