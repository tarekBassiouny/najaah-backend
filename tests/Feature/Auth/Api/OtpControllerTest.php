<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class)->group('auth');

test('send returns token', function (): void {
    User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()
        ->send('1234567890', '+20')
        ->andReturn(['token' => 'abc']);

    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    $response->assertOk()->assertJson(['token' => 'abc']);
});
