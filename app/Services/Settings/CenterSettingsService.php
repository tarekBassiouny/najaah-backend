<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\DB;

class CenterSettingsService implements CenterSettingsServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService,
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /** @param array<string, mixed> $settings */
    public function update(User $actor, Center $center, array $settings): CenterSetting
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $center);
        $this->enforceSystemConstraints($settings);

        return DB::transaction(function () use ($center, $settings, $actor): CenterSetting {
            /** @var CenterSetting|null $existing */
            $existing = $center->setting()->withTrashed()->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            $currentSettings = $existing?->settings ?? $this->policySettingsService->centerDefaults($center);
            $mergedSettings = $this->mergeSettings($currentSettings, $settings);

            /** @var CenterSetting $setting */
            $setting = $center->setting()->updateOrCreate(
                ['center_id' => $center->id],
                ['settings' => $mergedSettings],
            );

            $this->syncCenterColumns($center, $mergedSettings);

            $fresh = $setting->fresh() ?? $setting;

            $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_UPDATED, [
                'center_id' => $center->id,
                'updated_keys' => array_keys($settings),
            ]);

            return $fresh;
        });
    }

    /**
     * Update feature flags for a center (system admin only).
     *
     * @param  array<string, bool>  $features
     */
    public function updateFeatures(User $actor, Center $center, array $features): CenterSetting
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            throw new DomainException(
                'Only system admin can manage feature flags.',
                ErrorCodes::FORBIDDEN,
                403
            );
        }

        // Filter to only allowed feature keys
        $allowedKeys = array_keys($this->policySettingsService->defaultFeatures());
        $features = array_intersect_key($features, array_flip($allowedKeys));

        return DB::transaction(function () use ($center, $features, $actor): CenterSetting {
            /** @var CenterSetting|null $existing */
            $existing = $center->setting()->withTrashed()->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            $currentSettings = $existing?->settings ?? $this->policySettingsService->centerDefaults($center);
            $currentFeatures = is_array($currentSettings['features'] ?? null) ? $currentSettings['features'] : $this->policySettingsService->defaultFeatures();
            $currentSettings['features'] = array_replace_recursive($currentFeatures, $features);

            /** @var CenterSetting $setting */
            $setting = $center->setting()->updateOrCreate(
                ['center_id' => $center->id],
                ['settings' => $currentSettings],
            );

            $fresh = $setting->fresh() ?? $setting;

            $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_UPDATED, [
                'center_id' => $center->id,
                'updated_keys' => ['features'],
                'features' => $features,
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
            'settings' => $this->policySettingsService->centerDefaults($center),
        ]);

        $currentSettings = is_array($setting->settings) ? $setting->settings : [];
        $mergedDefaults = $this->mergeSettings(
            $this->policySettingsService->centerDefaults($center),
            $currentSettings,
            true
        );

        if ($mergedDefaults !== $currentSettings) {
            $setting->settings = $mergedDefaults;
            $setting->save();
        }

        if ($setting->wasRecentlyCreated) {
            $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_CREATED, [
                'center_id' => $center->id,
            ]);
        }

        return $setting->fresh() ?? $setting;
    }

    /**
     * Validate that center settings respect system-level constraints.
     *
     * @param  array<string, mixed>  $settings
     */
    private function enforceSystemConstraints(array $settings): void
    {
        if (array_key_exists('features', $settings)) {
            throw new DomainException(
                'Feature flags can only be managed by system admin.',
                ErrorCodes::FORBIDDEN,
                403
            );
        }

        $constraints = $this->policySettingsService->systemConstraints();

        if (isset($settings['default_view_limit']) && is_numeric($settings['default_view_limit'])) {
            $max = (int) $constraints['max_view_limit'];
            if ((int) $settings['default_view_limit'] > $max) {
                throw new DomainException(
                    sprintf('View limit cannot exceed the system maximum of %d.', $max),
                    ErrorCodes::SYSTEM_LIMIT_EXCEEDED,
                    422
                );
            }
        }

        if (isset($settings['device_limit']) && is_numeric($settings['device_limit'])) {
            $max = (int) $constraints['max_device_limit'];
            if ((int) $settings['device_limit'] > $max) {
                throw new DomainException(
                    sprintf('Device limit cannot exceed the system maximum of %d.', $max),
                    ErrorCodes::SYSTEM_LIMIT_EXCEEDED,
                    422
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeSettings(array $current, array $incoming, bool $allowFeatures = false): array
    {
        $merged = $current;

        foreach ($incoming as $key => $value) {
            // Protect features from center admin writes via merge
            if ($key === 'features' && ! $allowFeatures) {
                continue;
            }

            if (is_array($value) && is_array($merged[$key] ?? null)) {
                $merged[$key] = array_replace_recursive($merged[$key], $value);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function syncCenterColumns(Center $center, array $settings): void
    {
        $branding = is_array($settings['branding'] ?? null) ? $settings['branding'] : [];

        $center->forceFill([
            'default_view_limit' => (int) ($settings['default_view_limit'] ?? $center->default_view_limit),
            'allow_extra_view_requests' => (bool) ($settings['allow_extra_view_requests'] ?? $center->allow_extra_view_requests),
            'pdf_download_permission' => (bool) ($settings['pdf_download_permission'] ?? $center->pdf_download_permission),
            'device_limit' => (int) ($settings['device_limit'] ?? $center->device_limit),
            'allow_guest_browsing' => (bool) ($settings['allow_guest_browsing'] ?? $center->allow_guest_browsing),
            'logo_url' => array_key_exists('logo_url', $branding) ? $branding['logo_url'] : $center->logo_url,
            'primary_color' => array_key_exists('primary_color', $branding) ? $branding['primary_color'] : $center->primary_color,
        ])->save();
    }
}
