<?php

declare(strict_types=1);

namespace App\Services\Bunny;

class BunnyWebhookVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function verify(array $payload, ?string $signature): bool
    {
        $secret = config('bunny.api.webhook_secret');

        if ($secret === null || $signature === null) {
            return false;
        }

        $body = json_encode($payload);
        if ($body === false) {
            return false;
        }

        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $signature);
    }
}
