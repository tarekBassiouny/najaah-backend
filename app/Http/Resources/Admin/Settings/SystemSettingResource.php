<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Settings;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SystemSetting
 */
class SystemSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SystemSetting $setting */
        $setting = $this->resource;

        return [
            'id' => $setting->id,
            'key' => $setting->key,
            'value' => $setting->value,
            'is_public' => (bool) $setting->is_public,
            'created_at' => $setting->created_at?->toIso8601String(),
            'updated_at' => $setting->updated_at?->toIso8601String(),
        ];
    }
}
