<?php

declare(strict_types=1);

use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('auth', 'api');

test('send otp endpoint creates record', function (): void {
    $user = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    $response->assertOk();
    assertDatabaseHas('otp_codes', [
        'phone' => '1234567890',
    ]);
});

test('verify otp issues tokens', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone' => '1234567890', 'country_code' => '+20']);
    $otp = OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1234567890',
        'country_code' => '+20',
        'otp_code' => '123456',
        'token' => 'token123',
    ]);
    $device = UserDevice::factory()->create(['user_id' => $user->id]);
    JwtToken::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token123',
        'device_uuid' => $device->device_id,
    ]);

    $response->assertOk()->assertJsonStructure([
        'user',
        'device',
        'tokens' => ['access_token', 'refresh_token'],
    ]);
});
