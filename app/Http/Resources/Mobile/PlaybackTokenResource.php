<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ArrayAccess<string, mixed>
 */
class PlaybackTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return array_filter([
            'session_id' => $data['session_id'] ?? null,
            'embed_url' => $data['embed_url'] ?? null,
            'embed_token_expires_at' => $data['embed_token_expires_at'] ?? null,
            'embed_token_expires' => $data['embed_token_expires'] ?? null,
            'session_expires_at' => $data['session_expires_at'] ?? null,
            'session_expires_in' => $data['session_expires_in'] ?? null,
        ], static fn ($value): bool => $value !== null);
    }
}
