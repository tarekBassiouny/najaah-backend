<?php

declare(strict_types=1);

use App\Actions\Mobile\LoginAction;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Students\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('auth', 'actions', 'mobile');

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

    $studentService = \Mockery::mock(StudentService::class);
    $studentService->shouldNotReceive('create');

    $action = new LoginAction($otpService, $deviceService, $jwtService, $studentService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    expect($result)->not()->toBeNull();
    expect($result['user']->id)->toBe($user->id);
    expect($result['token']['access_token'])->toBe('access');
});

test('execute returns error when otp invalid', function (): void {
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

    $studentService = \Mockery::mock(StudentService::class);
    $studentService->shouldNotReceive('create');

    $action = new LoginAction($otpService, $deviceService, $jwtService, $studentService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    expect($result)->toBe(['error' => 'INVALID_OTP']);
});

test('execute returns error when center mismatches', function (): void {
    $user = User::factory()->make(['center_id' => 10]);
    $otpCode = OtpCode::factory()->make();
    $otpCode->setRelation('user', $user);

    $otpService = \Mockery::mock(OtpServiceInterface::class);
    $otpService->shouldReceive('verify')
        ->once()
        ->with('123456', 'token-123')
        ->andReturn($otpCode);

    $deviceService = \Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldNotReceive('register');

    $jwtService = \Mockery::mock(JwtServiceInterface::class);
    $jwtService->shouldNotReceive('create');

    $studentService = \Mockery::mock(StudentService::class);
    $studentService->shouldNotReceive('create');

    $action = new LoginAction($otpService, $deviceService, $jwtService, $studentService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ], 99);

    expect($result)->toBe(['error' => 'CENTER_MISMATCH']);
});

test('execute creates student when otp has no user', function (): void {
    $otpCode = OtpCode::factory()->create([
        'user_id' => null,
        'phone' => '1000000000',
        'country_code' => '+2',
        'otp_code' => '123456',
        'otp_token' => 'token-123',
    ]);

    $newUser = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
        'phone' => '1000000000',
        'country_code' => '+2',
    ]);

    $device = UserDevice::factory()->make(['user_id' => $newUser->id]);

    $otpService = \Mockery::mock(OtpServiceInterface::class);
    $otpService->shouldReceive('verify')
        ->once()
        ->with('123456', 'token-123')
        ->andReturn($otpCode);

    $studentService = \Mockery::mock(StudentService::class);
    $studentService->shouldReceive('create')
        ->once()
        ->with(\Mockery::on(function (array $payload): bool {
            return $payload['phone'] === '1000000000'
                && $payload['country_code'] === '+2'
                && $payload['center_id'] === null;
        }))
        ->andReturn($newUser);

    $deviceService = \Mockery::mock(DeviceServiceInterface::class);
    $deviceService->shouldReceive('register')
        ->once()
        ->with($newUser, 'device-1', \Mockery::type('array'))
        ->andReturn($device);

    $jwtService = \Mockery::mock(JwtServiceInterface::class);
    $jwtService->shouldReceive('create')
        ->once()
        ->with($newUser, $device)
        ->andReturn([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
        ]);

    $action = new LoginAction($otpService, $deviceService, $jwtService, $studentService);

    $result = $action->execute([
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ]);

    $otpCode->refresh();
    expect($otpCode->user_id)->toBe($newUser->id);
    expect($result)->not()->toBeNull();
});
