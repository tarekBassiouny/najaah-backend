<?php

declare(strict_types=1);

use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class)->group('web', 'auth', 'student');

function ensureCenterApiKey(Center $center): string
{
    if (! is_string($center->api_key) || $center->api_key === '') {
        $center->api_key = 'center-key-'.$center->id;
        $center->save();
    }

    return $center->api_key;
}

/*
|--------------------------------------------------------------------------
| 6.1 — Web student auth: send-otp, verify, refresh, logout
|--------------------------------------------------------------------------
*/

test('student send-otp returns token', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = ensureCenterApiKey($center);

    User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()->send('1234567890', '+20', (int) $center->id)->andReturn('otp-token-abc');
    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/web/auth/student/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('token', 'otp-token-abc');
});

test('student verify returns user data and tokens', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = ensureCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'verify-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'verify-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.is_student', true)
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'phone', 'is_student', 'is_parent'],
            'token' => ['access_token', 'refresh_token'],
            'device_uuid',
        ]);

    $this->assertDatabaseHas('jwt_tokens', [
        'user_id' => $student->id,
        'platform' => TokenPlatform::Web->value,
    ]);
});

test('student verify rejects invalid otp', function (): void {
    config(['services.system_api_key' => 'system-key']);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => 'wrong',
        'token' => 'bad-token',
    ], ['X-Api-Key' => 'system-key']);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'OTP_INVALID');
});

test('student verify rejects non-student user', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = ensureCenterApiKey($center);

    $parent = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => false,
        'is_parent' => true,
        'status' => UserStatus::Active,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'verify-token',
        'user_id' => $parent->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'verify-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'NOT_STUDENT');
});

test('student verify rejects banned student', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = ensureCenterApiKey($center);

    $student = User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
        'center_id' => $center->id,
        'is_student' => true,
        'status' => UserStatus::Banned,
    ]);

    OtpCode::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_token' => 'verify-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'verify-token',
    ], ['X-Api-Key' => $apiKey]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'STUDENT_BANNED');
});

test('student refresh returns new tokens', function (): void {
    $center = $this->createWebEnabledCenter();
    $student = $this->asWebStudent(center: $center);

    $refreshToken = JwtToken::where('user_id', $student->id)->first()->refresh_token;

    $response = $this->postJson('/api/v1/web/auth/student/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['token' => ['access_token', 'refresh_token']]);
});

test('student logout revokes token', function (): void {
    $center = $this->createWebEnabledCenter();
    $this->asWebStudent(center: $center);

    $response = $this->postJson('/api/v1/web/auth/student/logout');

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('student me returns profile', function (): void {
    $center = $this->createWebEnabledCenter();
    $student = $this->asWebStudent(center: $center);

    $response = $this->getJson('/api/v1/web/auth/student/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.is_student', true);
});

/*
|--------------------------------------------------------------------------
| 6.4 — Token platform tracking
|--------------------------------------------------------------------------
*/

test('web student token has platform web', function (): void {
    $center = $this->createWebEnabledCenter();
    $apiKey = ensureCenterApiKey($center);

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
        'otp_token' => 'platform-token',
        'user_id' => $student->id,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson('/api/v1/web/auth/student/verify', [
        'otp' => '123456',
        'token' => 'platform-token',
    ], ['X-Api-Key' => $apiKey])->assertOk();

    $jwtRecord = JwtToken::where('user_id', $student->id)->latest()->first();
    expect($jwtRecord->platform)->toBe(TokenPlatform::Web);
});
