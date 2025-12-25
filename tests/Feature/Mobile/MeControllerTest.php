<?php

declare(strict_types=1);

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class)->group('me', 'mobile');

beforeEach(function (): void {
    config(['services.system_api_key' => 'system-key']);
});

function authHeaders(?string $token = null): array
{
    $headers = [
        'X-Api-Key' => 'system-key',
    ];

    if (is_string($token) && $token !== '') {
        $headers['Authorization'] = 'Bearer '.$token;
    }

    return $headers;
}

test('returns current student on /auth/me', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.center.id', $user->center_id);
});

test('rejects unauthorized /auth/me', function (): void {
    $response = $this->getJson('/api/v1/auth/me', authHeaders());

    $response->assertStatus(403);
});

test('revokes token on logout and blocks reuse', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $logout = $this->postJson('/api/v1/auth/logout', [], authHeaders($access));

    $logout->assertOk()->assertJsonPath('success', true);

    $reuse = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $reuse->assertStatus(403);
});

test('blocks revoked access tokens', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
        'revoked_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('blocks expired access tokens', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->subMinute(),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('blocks tokens for revoked devices', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'status' => UserDevice::STATUS_REVOKED,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($access));

    $response->assertStatus(403);
});

test('allows system-level students without center assignment', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $token = JWTAuth::fromUser($student);
    JwtToken::create([
        'user_id' => $student->id,
        'device_id' => null,
        'access_token' => $token,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->getJson('/api/v1/auth/me', authHeaders($token));

    $response->assertOk()
        ->assertJsonPath('data.center_id', null)
        ->assertJsonPath('data.center.id', null);
});

test('updates student profile name', function (): void {
    $user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'name' => 'Old Name',
    ]);

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
    ]);

    $access = JWTAuth::fromUser($user);

    JwtToken::create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $response = $this->postJson('/api/v1/auth/me', [
        'name' => 'New Name',
    ], authHeaders($access));

    $response->assertOk()->assertJsonPath('data.name', 'New Name');
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});
