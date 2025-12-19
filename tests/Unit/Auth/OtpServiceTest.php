<?php

declare(strict_types=1);

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates otp record and sends via provider', function (): void {
    $startedAt = now();
    $user = User::factory()->create([
        'phone' => '555000111',
        'country_code' => '+1',
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
    $response = $service->send('555000111', '+1');

    expect($response)->toHaveKey('token')
        ->and($response['token'])->not->toBeEmpty();

    $otpRecord = OtpCode::where('phone', '555000111')->first();
    expect($otpRecord)->not->toBeNull()
        ->and($otpRecord->user_id)->toBe($user->id)
        ->and($otpRecord->provider)->toBe('whatsapp')
        ->and($otpRecord->otp_token)->toBe($response['token'])
        ->and($otpRecord->expires_at)->not->toBeNull()
        ->and($otpRecord->expires_at->greaterThan($startedAt))->toBeTrue();
});
