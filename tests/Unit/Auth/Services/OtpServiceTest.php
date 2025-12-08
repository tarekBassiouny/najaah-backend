<?php

declare(strict_types=1);

use App\Models\OtpCode;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, DatabaseTransactions::class)->group('auth', 'services');

test('send creates otp record and returns token', function (): void {
    $service = new OtpService;

    $result = $service->send('1234567890', '+20');

    expect($result)->toHaveKey('token');
    expect($result['token'])->toBeString();

    assertDatabaseHas('otp_codes', [
        'phone' => '1234567890',
        'country_code' => '+20',
        'otp_token' => $result['token'],
    ]);
});

test('verify returns otp code when valid', function (): void {
    /** @var OtpCode $code */
    $code = OtpCode::factory()->create([
        'otp_code' => '123456',
        'otp_token' => 'token-123',
        'expires_at' => now()->addMinutes(5),
    ]);

    $service = new OtpService;
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

    $service = new OtpService;
    $result = $service->verify('123456', 'token-123');

    expect($result)->toBeNull();
});
