<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Models\User;

class BunnyEmbedTokenService
{
    /**
     * @return array{token:string,expires:int}
     */
    public function generateForVideo(string $videoUuid, int $ttlSeconds = 600): array
    {
        $secret = config('bunny.embed_key');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Missing Bunny embed key.');
        }

        $expiresAt = (int) now()->addSeconds($ttlSeconds)->timestamp;
        $token = hash('sha256', $secret.$videoUuid.$expiresAt);

        return [
            'token' => $token,
            'expires' => $expiresAt,
        ];
    }

    /**
     * @return array{token:string,expires:int}
     */
    public function generate(
        string $videoUuid,
        User $student,
        int $centerId,
        int $enrollmentId,
        int $ttlSeconds = 600
    ): array {
        return $this->generateForVideo($videoUuid, $ttlSeconds);
    }
}
