<?php

declare(strict_types=1);

use App\Services\Auth\Contracts\JwtServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class)->group('auth');

test('refresh returns tokens', function (): void {
    /** @var MockInterface&JwtServiceInterface $jwt */
    $jwt = Mockery::mock(JwtServiceInterface::class);
    /** @var Mockery\ExpectationInterface $expectation */
    $jwt->allows()
        ->refresh('refresh-123')
        ->andReturn(['access_token' => 'access', 'refresh_token' => 'refresh']);

    $this->app->instance(JwtServiceInterface::class, $jwt);

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'refresh-123',
    ]);

    $response->assertOk()->assertJson([
        'access_token' => 'access',
        'refresh_token' => 'refresh',
    ]);
});
