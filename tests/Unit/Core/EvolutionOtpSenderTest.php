<?php

declare(strict_types=1);

use App\Services\Auth\Senders\EvolutionOtpSender;
use App\Services\Evolution\EvolutionApiClient;
use App\Services\Logging\LogContextResolver;
use Tests\TestCase;

uses(TestCase::class)->group('auth', 'core', 'mobile');

it('sends otp through evolution text message', function (): void {
    config([
        'evolution.otp_instance_name' => 'najaah-local',
        'evolution.otp_instance_token' => 'instance-token',
        'evolution.otp_message_template' => 'OTP {{otp}}',
    ]);

    $client = Mockery::mock(EvolutionApiClient::class);
    $client->shouldReceive('sendText')
        ->once()
        ->with('najaah-local', [
            'number' => '201234567890',
            'text' => 'OTP 123456',
        ])
        ->andReturn(['key' => ['id' => 'msg-1']]);

    $sender = new EvolutionOtpSender($client, app(LogContextResolver::class));
    $sender->send('+20 1234567890', '123456');
});

it('throws when evolution otp instance name is missing', function (): void {
    config([
        'evolution.otp_instance_name' => '',
    ]);

    $client = Mockery::mock(EvolutionApiClient::class);
    $sender = new EvolutionOtpSender($client, app(LogContextResolver::class));

    expect(fn () => $sender->send('+201234567890', '123456'))
        ->toThrow(RuntimeException::class, 'Evolution OTP instance name is missing.');
});
