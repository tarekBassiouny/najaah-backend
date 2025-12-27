<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Centers;

use App\Models\CenterSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CenterSetting
 */
class CenterSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CenterSetting $setting */
        $setting = $this->resource;

        return [
            'id' => $setting->id,
            'center_id' => $setting->center_id,
            'settings' => $setting->settings,
            'created_at' => $setting->created_at,
            'updated_at' => $setting->updated_at,
        ];
    }
}
