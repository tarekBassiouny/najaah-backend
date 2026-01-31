<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\CenterTier;
use App\Enums\CenterType;
use App\Models\Center;
use App\Services\Branding\CenterLogoUrlResolver;
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
        $logoUrlResolver = app(CenterLogoUrlResolver::class);

        $logoUrl = $branding['logo_url'] ?? $center->logo_url;
        $primaryColor = $branding['primary_color'] ?? $center->primary_color;

        return [
            'id' => $center->id,
            'slug' => $center->slug,
            'name' => $center->translate('name'),
            'type' => $this->resolveType($center->type),
            'tier' => $this->resolveTier($center->tier),
            'branding' => [
                'logo_url' => $logoUrlResolver->resolve($logoUrl),
                'primary_color' => $primaryColor,
            ],
        ];
    }

    private function resolveType(?CenterType $type): string
    {
        return match ($type) {
            Center::TYPE_BRANDED => 'branded',
            default => 'unbranded',
        };
    }

    private function resolveTier(?CenterTier $tier): string
    {
        return match ($tier) {
            Center::TIER_PREMIUM => 'premium',
            Center::TIER_VIP => 'vip',
            default => 'standard',
        };
    }
}
