<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Facades\DB;

class CenterSettingsService implements CenterSettingsServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
    ) {}

    /** @param array<string, mixed> $settings */
    public function update(User $actor, Center $center, array $settings): CenterSetting
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $center);

        return DB::transaction(function () use ($center, $settings, $actor): CenterSetting {
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

            $fresh = $setting->fresh() ?? $setting;

            $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_UPDATED, [
                'center_id' => $center->id,
                'updated_keys' => array_keys($settings),
            ]);

            return $fresh;
        });
    }

    public function get(User $actor, Center $center): CenterSetting
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $center);

        /** @var CenterSetting $setting */
        $setting = $center->setting()->firstOrCreate([
            'center_id' => $center->id,
        ], [
            'settings' => [],
        ]);

        if ($setting->wasRecentlyCreated) {
            $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_CREATED, [
                'center_id' => $center->id,
            ]);
        }

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
