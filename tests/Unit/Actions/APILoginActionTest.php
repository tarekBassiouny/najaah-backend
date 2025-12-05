<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Auth\APILoginAction;
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

class APILoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_returns_payload_when_otp_valid(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $device */
        $device = UserDevice::factory()->make(['user_id' => $user->id]);

        /** @var OtpCode $otpCode */
        $otpCode = OtpCode::factory()->make();
        $otpCode->setRelation('user', $user);

        /** @var MockInterface&OtpServiceInterface $otpService */
        $otpService = Mockery::mock(OtpServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $otpService->shouldReceive('verify')->once()->with('123456', 'token-123')->andReturn($otpCode);

        /** @var MockInterface&DeviceServiceInterface $deviceService */
        $deviceService = Mockery::mock(DeviceServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $deviceService->shouldReceive('register')->once()->andReturn($device);

        /** @var MockInterface&JwtServiceInterface $jwtService */
        $jwtService = Mockery::mock(JwtServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $jwtService->shouldReceive('create')->once()->andReturn(['access_token' => 'access', 'refresh_token' => 'refresh']);

        $action = new APILoginAction($otpService, $deviceService, $jwtService);

        $result = $action->execute([
            'otp' => '123456',
            'token' => 'token-123',
            'device_uuid' => 'device-1',
        ]);

        $this->assertNotNull($result);
        $this->assertSame($user->id, $result['user']->id);
        $this->assertSame('access', $result['tokens']['access_token']);
    }

    public function test_execute_returns_null_when_otp_invalid(): void
    {
        /** @var MockInterface&OtpServiceInterface $otpService */
        $otpService = Mockery::mock(OtpServiceInterface::class);
        /** @phpstan-ignore-next-line Mockery dynamic expectation */
        $otpService->shouldReceive('verify')->once()->with('123456', 'token-123')->andReturnNull();

        /** @var MockInterface&DeviceServiceInterface $deviceService */
        $deviceService = Mockery::mock(DeviceServiceInterface::class);
        /** @var MockInterface&JwtServiceInterface $jwtService */
        $jwtService = Mockery::mock(JwtServiceInterface::class);

        $action = new APILoginAction($otpService, $deviceService, $jwtService);

        $this->assertNull($action->execute([
            'otp' => '123456',
            'token' => 'token-123',
            'device_uuid' => 'device-1',
        ]));
    }
}
