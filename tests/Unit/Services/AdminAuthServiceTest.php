<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AdminAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?AdminAuthService $service = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminAuthService;
    }

    public function test_login_returns_user_and_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotNull($this->service);
        $result = $this->service->login('admin@example.com', 'secret123');

        $this->assertNotNull($result);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_returns_null_on_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertNotNull($this->service);
        $result = $this->service->login('admin@example.com', 'wrong');

        $this->assertNull($result);
    }

    public function test_logout_revokes_current_token(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('admin');
        $user->withAccessToken($token->accessToken);

        $this->assertNotNull($this->service);
        $this->service->logout($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_me_returns_authenticated_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->assertNotNull($this->service);
        $service = $this->service;

        $this->assertSame($user->id, $service->me($user)?->id);
        $this->assertNull($service->me(null));
    }
}
