<?php

declare(strict_types=1);

namespace App\Services\Auth\Senders;

use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Logging\LogContextResolver;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpSender implements OtpSenderInterface
{
    public function send(string $destination, string $otp): void
    {
        try {
            $this->sendViaProvider($destination, $otp);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp OTP send failed.', $this->resolveLogContext([
                'error' => $exception->getMessage(),
            ]));
            throw $exception;
        }
    }

    public function provider(): string
    {
        return 'whatsapp';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        unset($overrides['otp'], $overrides['token']);

        return app(LogContextResolver::class)->resolve($overrides);
    }

    /**
     * @throws \Throwable
     */
    private function sendViaProvider(string $destination, string $otp): void
    {
        // WhatsApp provider integration goes here.
    }
}
