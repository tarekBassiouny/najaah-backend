<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OtpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_returns_token(): void
    {
        /** @var MockInterface&OtpServiceInterface $otp */
        $otp = Mockery::mock(OtpServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $otp->shouldReceive('send')
            ->once()
            ->with('+201234567890', '+20')
            ->andReturn(['token' => 'abc']);

        $this->app->instance(OtpServiceInterface::class, $otp);

        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '+201234567890',
            'country_code' => '+20',
        ]);

        $response->assertOk()->assertJson(['token' => 'abc']);
    }
}
