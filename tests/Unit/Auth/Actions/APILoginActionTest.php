<?php

declare(strict_types=1);

use App\Actions\Auth\APILoginAction;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use Tests\TestCase;

uses(TestCase::class)->group('auth', 'actions');

test('execute returns payload when otp valid', function (): void {

    // UNIT TEST → use make() not create()
    $user = User::factory()->make();

    $device = UserDevice::factory()->make(['user_id' => $user->id]);

    $otpCode = OtpCode::factory()->make();
    $otpCode->setRelation('user', $user);

    // Correct Mockery usage
    $otpService = \Mockery::mock(OtpServiceInterface::class);
    $otpService->shouldReceive('verify')
        ->once()
        ->with('123456', 'token-123')
        ->andReturn($otpCode);

    $deviceService = \Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('register')
        ->once()
        ->with($user, 'device-1', \Mockery::type('array'))
        ->andReturn($device);

    $jwtService = \Mockery::mock(JwtServiceInterface::class);
    $jwtService->shouldReceive('create')
        ->once()
        ->with($user, $device) // Use the signature your service expects
        ->andReturn([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
        ]);

    $action = new APILoginAction($otpService, $deviceService, $jwtService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    expect($result)->not()->toBeNull();
    expect($result['user']->id)->toBe($user->id);
    expect($result['tokens']['access_token'])->toBe('access');
});

test('execute returns null when otp invalid', function (): void {
    // OTP verification returns null
    $otpService = \Mockery::mock(OtpServiceInterface::class);
    $otpService->shouldReceive('verify')
        ->once()
        ->with('123456', 'token-123')
        ->andReturnNull();

    // These services must NOT be called when OTP is invalid
    $deviceService = \Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldNotReceive('register');

    $jwtService = \Mockery::mock(JwtServiceInterface::class);
    $jwtService->shouldNotReceive('create');

    $action = new APILoginAction($otpService, $deviceService, $jwtService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    expect($result)->toBeNull();
});
