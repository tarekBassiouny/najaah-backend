<?php

declare(strict_types=1);

use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('auth', 'api');

test('send otp endpoint creates record', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake([
        'https://graph.facebook.com/v19.0/12345/messages' => Http::response([
            'messages' => [['id' => 'msg-1']],
        ], 200),
    ]);

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

test('otp cannot be reused after consumption', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone' => '5555555555', 'country_code' => '+20']);
    $otp = OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '5555555555',
        'country_code' => '+20',
        'otp_code' => '654321',
        'token' => 'tok-reuse',
    ]);

    $this->postJson('/api/v1/auth/verify', [
        'otp' => '654321',
        'token' => 'tok-reuse',
        'device_uuid' => 'device-reuse',
    ])->assertOk();

    $otp->refresh();
    expect($otp->consumed_at)->not->toBeNull();

    $this->postJson('/api/v1/auth/verify', [
        'otp' => '654321',
        'token' => 'tok-reuse',
        'device_uuid' => 'device-reuse',
    ])->assertStatus(422);
});
