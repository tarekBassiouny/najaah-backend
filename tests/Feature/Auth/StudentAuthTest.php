<?php

declare(strict_types=1);

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class)->group('auth', 'api');

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
    ]);

    $this->device = UserDevice::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $access = JWTAuth::fromUser($this->user);

    JwtToken::create([
        'user_id' => $this->user->id,
        'device_id' => $this->device->id,
        'access_token' => $access,
        'refresh_token' => 'refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDays(30),
    ]);

    $this->token = $access;
});

it('returns current student on /auth/me', function (): void {
    $response = $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->user->id);
});

it('rejects unauthorized /auth/me', function (): void {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(403);
});

it('revokes token on logout and blocks reuse', function (): void {
    $logout = $this->postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $logout->assertOk()->assertJsonPath('success', true);

    $reuse = $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$this->token,
    ]);

    $reuse->assertStatus(403);
});
