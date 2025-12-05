<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?JwtService $service = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JwtService;
    }

    public function test_create_stores_refresh_token_and_returns_tokens(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $device */
        $device = UserDevice::factory()->create(['user_id' => $user->id]);

        JWTAuth::shouldReceive('fromUser')->once()->with($user)->andReturn('access-token');

        $this->assertNotNull($this->service);
        $tokens = $this->service->create($user, $device);

        $this->assertSame('access-token', $tokens['access_token']);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertDatabaseHas('jwt_tokens', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'access_token' => 'access-token',
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    public function test_refresh_updates_access_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var JwtToken $token */
        $token = JwtToken::factory()->create([
            'user_id' => $user->id,
            'refresh_token' => 'refresh-123',
            'refresh_expires_at' => now()->addDay(),
            'access_token' => 'old-access',
        ]);

        JWTAuth::shouldReceive('fromUser')->once()->with(Mockery::type(User::class))->andReturn('new-access');

        $this->assertNotNull($this->service);
        $result = $this->service->refresh('refresh-123');

        $this->assertSame('new-access', $result['access_token']);
        $this->assertSame('refresh-123', $result['refresh_token']);
        $this->assertDatabaseHas('jwt_tokens', [
            'id' => $token->id,
            'access_token' => 'new-access',
        ]);
    }

    public function test_refresh_returns_empty_tokens_when_not_found(): void
    {
        $this->assertNotNull($this->service);
        $result = $this->service->refresh('missing');

        $this->assertSame('', $result['access_token']);
        $this->assertSame('', $result['refresh_token']);
    }

    public function test_create_persists_long_access_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $device */
        $device = UserDevice::factory()->create(['user_id' => $user->id]);

        $longToken = str_repeat('a', 600);
        JWTAuth::shouldReceive('fromUser')->once()->with($user)->andReturn($longToken);

        $this->assertNotNull($this->service);
        $tokens = $this->service->create($user, $device);

        $this->assertSame($longToken, $tokens['access_token']);
        $this->assertDatabaseHas('jwt_tokens', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'access_token' => $longToken,
        ]);
    }
}
