<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ArrayAccess<string, mixed>
 */
class PlaybackSessionResource extends JsonResource
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
            'is_locked' => $data['is_locked'] ?? null,
            'remaining_views' => $data['remaining_views'] ?? null,
            'view_limit' => $data['view_limit'] ?? null,
        ], static fn ($value): bool => $value !== null);
    }
}
