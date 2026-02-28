<?php

declare(strict_types=1);

namespace App\Services\Auth\Senders;

use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Evolution\EvolutionApiClient;
use App\Services\Logging\LogContextResolver;
use Illuminate\Support\Facades\Log;

class EvolutionOtpSender implements OtpSenderInterface
{
    public function __construct(
        private readonly EvolutionApiClient $client,
        private readonly LogContextResolver $logContextResolver
    ) {}

    public function send(string $destination, string $otp): void
    {
        try {
            $instanceName = (string) config('evolution.otp_instance_name', '');

            if ($instanceName === '') {
                throw new \RuntimeException('Evolution OTP instance name is missing.');
            }

            $messageTemplate = (string) config('evolution.otp_message_template', 'Your Najaah OTP is {{otp}}');
            $message = str_replace('{{otp}}', $otp, $messageTemplate);

            $this->client->sendText($instanceName, [
                'number' => $this->normalizeDestination($destination),
                'text' => $message,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Evolution OTP send failed.', $this->resolveLogContext([
                'provider' => $this->provider(),
                'exception_class' => $throwable::class,
                'error' => $throwable->getMessage(),
            ]));

            throw $throwable;
        }
    }

    public function provider(): string
    {
        return 'evolution';
    }

    private function normalizeDestination(string $destination): string
    {
        $normalized = preg_replace('/\D+/', '', $destination) ?? '';

        if ($normalized === '') {
            throw new \RuntimeException('Evolution destination must contain digits.');
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        unset($overrides['otp'], $overrides['token']);

        return $this->logContextResolver->resolve($overrides);
    }
}
