<?php

declare(strict_types=1);

use App\Services\Auth\Senders\WhatsAppOtpSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class)->group('auth', 'core', 'mobile');

it('calls whatsapp api with template payload', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake(function (Request $request) {
        expect($request->url())->toBe('https://graph.facebook.com/v19.0/12345/messages');
        expect($request->hasHeader('Authorization'))->toBeTrue();

        $data = $request->data();
        expect($data['messaging_product'])->toBe('whatsapp')
            ->and($data['type'])->toBe('template')
            ->and($data['template']['name'])->toBe('otp_auth')
            ->and($data['template']['language']['code'])->toBe('en')
            ->and($data['template']['components'][0]['parameters'][0]['text'])->toBe('123456');

        return Http::response(['messages' => [['id' => 'msg-1']]], 200);
    });

    $sender = new WhatsAppOtpSender;
    $sender->send('001555000111', '123456');

    Http::assertSentCount(1);
});

it('throws on whatsapp api error response', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake([
        'https://graph.facebook.com/v19.0/12345/messages' => Http::response([
            'error' => [
                'message' => 'Invalid request',
            ],
        ], 400),
    ]);

    $sender = new WhatsAppOtpSender;

    expect(fn () => $sender->send('+1555000111', '123456'))
        ->toThrow(RuntimeException::class);
});

it('keeps + destination unchanged', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake(function (Request $request) {
        $data = $request->data();
        expect($data['to'])->toBe('+1555000111');

        return Http::response(['messages' => [['id' => 'msg-2']]], 200);
    });

    $sender = new WhatsAppOtpSender;
    $sender->send('+1555000111', '123456');
});

it('throws for invalid destination', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake();

    $sender = new WhatsAppOtpSender;

    expect(fn () => $sender->send('1555000111', '123456'))
        ->toThrow(RuntimeException::class);
});
