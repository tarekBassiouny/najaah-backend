<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\JwtServiceInterface;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_issues_tokens(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var OtpCode $otpCode */
        $otpCode = OtpCode::factory()->make(['user_id' => $user->id]);
        $otpCode->setRelation('user', $user);

        /** @var MockInterface&OtpServiceInterface $otpService */
        $otpService = Mockery::mock(OtpServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $otpService->shouldReceive('verify')
            ->once()
            ->with('123456', 'token-123')
            ->andReturn($otpCode);
        $this->app->instance(OtpServiceInterface::class, $otpService);

        /** @var UserDevice $device */
        $device = UserDevice::factory()->make(['user_id' => $user->id]);
        /** @var MockInterface&DeviceServiceInterface $deviceService */
        $deviceService = Mockery::mock(DeviceServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $deviceService->shouldReceive('register')
            ->once()
            ->andReturn($device);
        $this->app->instance(DeviceServiceInterface::class, $deviceService);

        /** @var MockInterface&JwtServiceInterface $jwtService */
        $jwtService = Mockery::mock(JwtServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $jwtService->shouldReceive('create')
            ->once()
            ->andReturn(['access_token' => 'access', 'refresh_token' => 'refresh']);
        $this->app->instance(JwtServiceInterface::class, $jwtService);

        $response = $this->postJson('/api/v1/auth/verify', [
            'otp' => '123456',
            'token' => 'token-123',
            'device_uuid' => 'device-123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'device', 'tokens' => ['access_token', 'refresh_token']]);
    }
}
