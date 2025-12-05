<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Services\Contracts\JwtServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_returns_tokens(): void
    {
        /** @var MockInterface&JwtServiceInterface $jwt */
        $jwt = Mockery::mock(JwtServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $jwt->shouldReceive('refresh')
            ->once()
            ->with('refresh-123')
            ->andReturn(['access_token' => 'access', 'refresh_token' => 'refresh']);

        $this->app->instance(JwtServiceInterface::class, $jwt);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'refresh-123',
        ]);

        $response->assertOk()->assertJson([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
        ]);
    }
}
