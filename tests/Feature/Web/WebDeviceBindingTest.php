<?php

declare(strict_types=1);

use App\Enums\DeviceType;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('web', 'device');

function deviceCenterApiKey(Center $center): string
{
    if (! is_string($center->api_key) || $center->api_key === '') {
        $center->api_key = 'center-key-'.$center->id;
        $center->save();
    }

    return $center->api_key;
}

/*
|--------------------------------------------------------------------------
| 6.3 — Web device binding: register, limit enforcement, separate pool
|--------------------------------------------------------------------------
*/

test('web login creates device with web type', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = deviceCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'device-test-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'device-test-token',
        'device_uuid' => 'web-device-uuid-123',
        'device_name' => 'Chrome Browser',
        'device_os' => 'Windows 11',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk();

    $device = UserDevice::where('user_id', $student->id)
        ->where('device_id', 'web-device-uuid-123')
        ->first();

    expect($device)->not->toBeNull();
    expect($device->device_type)->toBe(DeviceType::Web->value);
});

test('web devices are separate pool from mobile devices', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = deviceCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    // Create an existing mobile device
    UserDevice::create([
        'user_id' => $student->id,
        'device_id' => 'mobile-device-uuid',
        'device_name' => 'iPhone 15',
        'device_type' => DeviceType::Mobile->value,
        'model' => 'iPhone',
        'os_version' => 'iOS 17',
        'status' => UserDevice::STATUS_ACTIVE,
        'approved_at' => now(),
        'last_used_at' => now(),
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'pool-test-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'pool-test-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk();

    $mobileCount = UserDevice::where('user_id', $student->id)
        ->where('device_type', DeviceType::Mobile->value)
        ->count();
    $webCount = UserDevice::where('user_id', $student->id)
        ->where('device_type', DeviceType::Web->value)
        ->count();

    expect($mobileCount)->toBe(1);
    expect($webCount)->toBe(1);
});

test('web login generates uuid when not provided', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = deviceCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'uuid-gen-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'uuid-gen-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonStructure(['device_uuid']);

    $deviceUuid = $response->json('device_uuid');
    expect($deviceUuid)->toBeString()->not->toBeEmpty();
});
