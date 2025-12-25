<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class CenterDiscoveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Center $center */
        $center = $this->resource;
        $type = $this->resolveType($center->type);
        $settings = $center->setting?->settings ?? [];
        $branding = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];

        $theme = array_filter([
            'logo_url' => $branding['logo_url'] ?? $center->logo_url,
            'primary_color' => $branding['primary_color'] ?? $center->primary_color,
        ], static fn ($value): bool => $value !== null);

        return [
            'id' => $type === 'system' ? null : $center->id,
            'slug' => $center->slug,
            'type' => $type,
            'name' => $center->name,
            'logo' => $center->logo_url,
            'theme' => (object) $theme,
        ];
    }

    private function resolveType(?int $type): string
    {
        return match ($type) {
            0 => 'unbranded',
            1 => 'branded',
            default => 'system',
        };
    }
}
