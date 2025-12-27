<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class resolvedCenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Center $center */
        $center = $this->resource;
        $settings = $center->setting?->settings ?? [];
        $branding = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];

        $logoUrl = $branding['logo_url'] ?? $center->logo_url;
        $primaryColor = $branding['primary_color'] ?? $center->primary_color;

        return [
            'id' => $center->id,
            'slug' => $center->slug,
            'name' => $center->name,
            'type' => $this->resolveType($center->type),
            'tier' => $this->resolveTier($center->tier),
            'branding' => [
                'logo_url' => $logoUrl,
                'primary_color' => $primaryColor,
            ],
        ];
    }

    private function resolveType(?int $type): string
    {
        return match ($type) {
            1 => 'branded',
            default => 'unbranded',
        };
    }

    private function resolveTier(?int $tier): string
    {
        return match ($tier) {
            Center::TIER_PREMIUM => 'premium',
            Center::TIER_VIP => 'vip',
            default => 'standard',
        };
    }
}
