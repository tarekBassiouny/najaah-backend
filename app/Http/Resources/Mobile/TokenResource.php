<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \ArrayAccess<string, mixed>
 */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $tokens */
        $tokens = $this->resource;

        return [
            'access_token' => $tokens['access_token'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_in' => $tokens['expires_in'] ?? 1800,
        ];
    }
}
