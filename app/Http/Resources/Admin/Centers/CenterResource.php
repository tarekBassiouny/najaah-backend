<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Centers;

use App\Models\Center;
use App\Services\Branding\CenterLogoUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class CenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Center $center */
        $center = $this->resource;
        $logoUrlResolver = app(CenterLogoUrlResolver::class);

        return [
            'id' => $center->id,
            'slug' => $center->slug,
            'type' => $this->resolveType($center->type),
            'tier' => $this->resolveTier($center->tier),
            'is_featured' => $center->is_featured,
            'name' => $center->translate('name'),
            'description' => $center->translate('description'),
            'name_translations' => $center->name_translations,
            'description_translations' => $center->description_translations,
            'logo_url' => $logoUrlResolver->resolve($center->logo_url),
            'primary_color' => $center->primary_color,
            'onboarding_status' => $center->onboarding_status,
            'branding_metadata' => $center->branding_metadata,
            'storage_driver' => $center->storage_driver,
            'storage_root' => $center->storage_root,
            'default_view_limit' => $center->default_view_limit,
            'allow_extra_view_requests' => $center->allow_extra_view_requests,
            'pdf_download_permission' => $center->pdf_download_permission,
            'device_limit' => $center->device_limit,
            'setting' => $center->setting,
        ];
    }

    private function resolveType(?int $type): string
    {
        return match ($type) {
            Center::TYPE_BRANDED => 'branded',
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
