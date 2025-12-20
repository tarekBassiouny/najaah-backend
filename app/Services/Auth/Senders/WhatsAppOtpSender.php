<?php

declare(strict_types=1);

namespace App\Services\Auth\Senders;

use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Logging\LogContextResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpSender implements OtpSenderInterface
{
    public function send(string $destination, string $otp): void
    {
        try {
            $this->sendViaProvider($destination, $otp);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp OTP send failed.', $this->resolveLogContext([
                'provider' => $this->provider(),
                'exception_class' => $exception::class,
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
        $accessToken = (string) config('whatsapp.access_token', '');
        $phoneNumberId = (string) config('whatsapp.phone_number_id', '');
        $apiVersion = (string) config('whatsapp.api_version', 'v19.0');
        $templateName = (string) config('whatsapp.otp_template', '');

        if ($accessToken === '' || $phoneNumberId === '' || $templateName === '') {
            throw new \RuntimeException('WhatsApp credentials or template are missing.');
        }

        $normalizedDestination = $this->normalizeDestination($destination);

        $url = sprintf('https://graph.facebook.com/%s/%s/messages', $apiVersion, $phoneNumberId);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $normalizedDestination,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $otp,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('WhatsApp API request failed with status '.$response->status().'.');
        }

        $data = $response->json();
        if (is_array($data) && array_key_exists('error', $data)) {
            throw new \RuntimeException('WhatsApp API returned an error response.');
        }
    }

    private function normalizeDestination(string $destination): string
    {
        $normalized = $destination;

        if (str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }

        if (! str_starts_with($normalized, '+')) {
            throw new \RuntimeException('WhatsApp destination must be in E.164 format.');
        }

        return $normalized;
    }
}
