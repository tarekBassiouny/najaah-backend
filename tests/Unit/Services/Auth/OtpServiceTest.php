<?php

declare(strict_types=1);

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, DatabaseTransactions::class)->group('auth', 'services', 'mobile');

test('send creates otp record and returns token', function (): void {
    $sender = Mockery::mock(OtpSenderInterface::class);
    $sender->shouldReceive('provider')
        ->once()
        ->andReturn('whatsapp');
    $sender->shouldReceive('send')
        ->once();

    $service = new OtpService($sender);

    $result = $service->send('1234567890', '+20', null);

    expect($result)->toBeString()
        ->and($result)->not->toBeEmpty();

    assertDatabaseHas('otp_codes', [
        'phone' => '1234567890',
        'country_code' => '+20',
        'otp_token' => $result,
    ]);
});

test('verify returns otp code when valid', function (): void {
    /** @var OtpCode $code */
    $code = OtpCode::factory()->create([
        'otp_code' => '123456',
        'otp_token' => 'token-123',
        'expires_at' => now()->addMinutes(5),
    ]);

    $sender = Mockery::mock(OtpSenderInterface::class);
    $service = new OtpService($sender);
    $result = $service->verify('123456', 'token-123');

    expect($result)->not()->toBeNull();
    expect($result?->is($code))->toBeTrue();
});

test('verify returns null when expired', function (): void {
    OtpCode::factory()->create([
        'otp_code' => '123456',
        'otp_token' => 'token-123',
        'expires_at' => now()->subMinute(),
    ]);

    $sender = Mockery::mock(OtpSenderInterface::class);
    $service = new OtpService($sender);
    $result = $service->verify('123456', 'token-123');

    expect($result)->toBeNull();
});

it('creates otp record and sends via provider', function (): void {
    $startedAt = now();
    $user = User::factory()->create([
        'phone' => '555000111',
        'country_code' => '+1',
        'is_student' => true,
        'center_id' => null,
    ]);

    $sender = Mockery::mock(OtpSenderInterface::class);
    $sender->shouldReceive('provider')
        ->once()
        ->andReturn('whatsapp');
    $sender->shouldReceive('send')
        ->once()
        ->with('+1555000111', Mockery::on(static fn ($otp): bool => is_string($otp) && $otp !== ''));

    app()->instance(OtpSenderInterface::class, $sender);

    $service = app(OtpService::class);
    $response = $service->send('555000111', '+1', null);

    expect($response)->toBeString()
        ->and($response)->not->toBeEmpty();

    $otpRecord = OtpCode::where('phone', '555000111')->first();
    expect($otpRecord)->not->toBeNull()
        ->and($otpRecord->user_id)->toBe($user->id)
        ->and($otpRecord->provider)->toBe('whatsapp')
        ->and($otpRecord->otp_token)->toBe($response)
        ->and($otpRecord->expires_at)->not->toBeNull()
        ->and($otpRecord->expires_at->greaterThan($startedAt))->toBeTrue();
});

it('matches otp send to system student when center scope is null', function (): void {
    $center = \App\Models\Center::factory()->create();

    User::factory()->create([
        'phone' => '777000111',
        'country_code' => '+1',
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    $systemStudent = User::factory()->create([
        'phone' => '777000111',
        'country_code' => '+1',
        'is_student' => true,
        'center_id' => null,
    ]);

    $sender = Mockery::mock(OtpSenderInterface::class);
    $sender->shouldReceive('provider')->once()->andReturn('whatsapp');
    $sender->shouldReceive('send')->once();

    $service = new OtpService($sender);
    $token = $service->send('777000111', '+1', null);

    assertDatabaseHas('otp_codes', [
        'otp_token' => $token,
        'user_id' => $systemStudent->id,
    ]);
});

it('matches otp send to center student when center scope is provided', function (): void {
    $center = \App\Models\Center::factory()->create();

    $centerStudent = User::factory()->create([
        'phone' => '888000111',
        'country_code' => '+1',
        'is_student' => true,
        'center_id' => $center->id,
    ]);

    User::factory()->create([
        'phone' => '888000111',
        'country_code' => '+1',
        'is_student' => true,
        'center_id' => null,
    ]);

    $sender = Mockery::mock(OtpSenderInterface::class);
    $sender->shouldReceive('provider')->once()->andReturn('whatsapp');
    $sender->shouldReceive('send')->once();

    $service = new OtpService($sender);
    $token = $service->send('888000111', '+1', (int) $center->id);

    assertDatabaseHas('otp_codes', [
        'otp_token' => $token,
        'user_id' => $centerStudent->id,
    ]);
});
