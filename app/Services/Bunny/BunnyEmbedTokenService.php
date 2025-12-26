<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Models\User;

class BunnyEmbedTokenService
{
    /**
     * @return array{token:string,expires_in:int}
     */
    public function generate(string $videoUuid, User $student, int $ttlSeconds = 600): array
    {
        $secret = config('bunny.api.api_key');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Missing Bunny Stream API key.');
        }

        $expiresAt = now()->addSeconds($ttlSeconds)->timestamp;
        $payload = $videoUuid.'|'.$student->id.'|'.$expiresAt;
        $hash = hash_hmac('sha256', $payload, $secret);

        $token = base64_encode($payload.'|'.$hash);
        $token = rtrim(strtr($token, '+/', '-_'), '=');

        return [
            'token' => $token,
            'expires_in' => $ttlSeconds,
        ];
    }
}
