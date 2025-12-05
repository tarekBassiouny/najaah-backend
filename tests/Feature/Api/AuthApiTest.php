<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_otp_endpoint_creates_record(): void
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '+201234567890',
            'country_code' => '+20',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertDatabaseHas('otp_codes', ['phone' => '+201234567890']);
    }

    public function test_verify_endpoint_registers_device_and_tokens(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        OtpCode::factory()->create([
            'user_id' => $user->id,
            'otp_code' => '123456',
            'otp_token' => 'token-abc',
            'expires_at' => now()->addMinutes(5),
        ]);

        JWTAuth::shouldReceive('fromUser')->andReturn('access-token');

        $response = $this->postJson('/api/v1/auth/verify', [
            'otp' => '123456',
            'token' => 'token-abc',
            'device_uuid' => 'device-123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'device', 'tokens' => ['access_token', 'refresh_token']]);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'device-123',
        ]);
        $this->assertDatabaseCount('jwt_tokens', 1);
    }

    public function test_refresh_endpoint_returns_new_access_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $device */
        $device = UserDevice::factory()->create(['user_id' => $user->id]);
        JwtToken::factory()->create([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'refresh_token' => 'refresh-123',
            'refresh_expires_at' => now()->addDay(),
        ]);

        JWTAuth::shouldReceive('fromUser')->andReturn('new-access');

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'refresh-123',
        ]);

        $response->assertOk()->assertJson([
            'access_token' => 'new-access',
            'refresh_token' => 'refresh-123',
        ]);
    }

    public function test_admin_login_me_and_logout_flow(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $login = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $login->assertOk()->assertJsonStructure(['token']);
        $token = $login->json('token');

        $me = $this->getJson('/api/v1/admin/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $me->assertOk()->assertJsonStructure(['user']);

        $logout = $this->postJson('/api/v1/admin/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $logout->assertOk()->assertJson(['message' => 'Logged out']);
    }
}
