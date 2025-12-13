<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Center;
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

        return [
            'id' => $center->id,
            'slug' => $center->slug,
            'type' => $center->type,
            'name' => $center->name,
            'description' => $center->description,
            'logo_url' => $center->logo_url,
            'primary_color' => $center->primary_color,
            'default_view_limit' => $center->default_view_limit,
            'allow_extra_view_requests' => $center->allow_extra_view_requests,
            'pdf_download_permission' => $center->pdf_download_permission,
            'device_limit' => $center->device_limit,
            'setting' => $center->setting,
        ];
    }
}
