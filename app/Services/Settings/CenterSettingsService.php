<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use Illuminate\Support\Facades\DB;

class CenterSettingsService implements CenterSettingsServiceInterface
{
    /** @param array<string, mixed> $settings */
    public function update(Center $center, array $settings): CenterSetting
    {
        return DB::transaction(function () use ($center, $settings): CenterSetting {
            /** @var CenterSetting|null $existing */
            $existing = $center->setting()->withTrashed()->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            $currentSettings = $existing?->settings ?? [];
            $mergedSettings = $this->mergeSettings($currentSettings, $settings);

            /** @var CenterSetting $setting */
            $setting = $center->setting()->updateOrCreate(
                ['center_id' => $center->id],
                ['settings' => $mergedSettings],
            );

            return $setting->fresh() ?? $setting;
        });
    }

    public function get(Center $center): CenterSetting
    {
        /** @var CenterSetting $setting */
        $setting = $center->setting()->firstOrCreate([
            'center_id' => $center->id,
        ], [
            'settings' => [],
        ]);

        return $setting->fresh() ?? $setting;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeSettings(array $current, array $incoming): array
    {
        $merged = $current;

        foreach ($incoming as $key => $value) {
            if ($key === 'branding' && is_array($value)) {
                $existingBranding = is_array($merged['branding'] ?? null) ? $merged['branding'] : [];
                $merged['branding'] = array_replace_recursive($existingBranding, $value);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}
