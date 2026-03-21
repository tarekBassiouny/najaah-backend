<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;

class PolicySettingsService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        $catalog = config('settings_catalog.catalog', []);

        return is_array($catalog) ? $catalog : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function centerSettingsCatalog(): array
    {
        return array_filter(
            $this->catalog(),
            static fn (array $definition): bool => ($definition['scope'] ?? null) === 'center'
                && ($definition['managed_by'] ?? 'center') === 'center'
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function systemSettingsCatalog(): array
    {
        return array_filter(
            $this->catalog(),
            static fn (array $definition): bool => ($definition['scope'] ?? null) === 'system'
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function systemSettingGroups(): array
    {
        return $this->groupDefinitions($this->systemSettingsCatalog());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function centerSettingGroups(): array
    {
        return $this->groupDefinitions($this->centerSettingsCatalog());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function featureFlagCatalog(): array
    {
        $properties = $this->catalog()['features']['properties'] ?? [];

        return is_array($properties) ? $properties : [];
    }

    /**
     * @return array<int, string>
     */
    public function centerSettingKeys(): array
    {
        return array_keys($this->centerSettingsCatalog());
    }

    /**
     * @return array<int, string>
     */
    public function systemSettingKeys(): array
    {
        return array_keys($this->systemSettingsCatalog());
    }

    /**
     * @return array<int, string>
     */
    public function featureFlagKeys(): array
    {
        return array_keys($this->featureFlagCatalog());
    }

    /**
     * @return array<int, string>
     */
    public function nestedKeysFor(string $key): array
    {
        $properties = $this->catalog()[$key]['properties'] ?? [];

        return is_array($properties) ? array_keys($properties) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function centerSettingRules(string $prefix = 'settings'): array
    {
        return $this->rulesFromDefinitions($this->centerSettingsCatalog(), $prefix);
    }

    /**
     * @return array<string, mixed>
     */
    public function featureFlagRules(string $prefix = 'features'): array
    {
        return $this->rulesFromDefinitions($this->featureFlagCatalog(), $prefix);
    }

    /**
     * @return array<string, mixed>
     */
    public function centerDefaults(Center $center): array
    {
        $branding = array_filter([
            'logo_url' => $center->logo_url,
            'primary_color' => $center->primary_color,
        ], static fn ($value): bool => $value !== null);

        return [
            'default_view_limit' => $center->default_view_limit,
            'allow_extra_view_requests' => $center->allow_extra_view_requests,
            'pdf_download_permission' => $center->pdf_download_permission,
            'device_limit' => $center->device_limit,
            'allow_guest_browsing' => $center->allow_guest_browsing,
            'branding' => $branding,
            'education_profile' => $this->defaultCatalogValue('education_profile', []),
            'features' => $this->defaultFeatures(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function systemDefaults(): array
    {
        $defaults = [];
        $settings = SystemSetting::query()
            ->whereIn('key', $this->systemSettingKeys())
            ->get()
            ->keyBy('key');

        foreach ($this->systemSettingsCatalog() as $key => $definition) {
            /** @var SystemSetting|null $setting */
            $setting = $settings->get($key);
            $defaults[$key] = $this->normalizeSystemValue($key, $setting?->value, $definition['default']);
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveCenterPolicy(Center $center): array
    {
        $resolved = $this->mergeRecursive(
            $this->systemDefaults(),
            $this->mergeRecursive($this->centerDefaults($center), $this->rawCenterSettings($center)),
        );

        return $this->applyCenterGovernance($center, $resolved);
    }

    /**
     * @return array<string, mixed>
     */
    public function rawCenterSettings(Center $center): array
    {
        $setting = $center->relationLoaded('setting') ? $center->setting : $center->setting()->first();
        $settings = $setting?->settings;

        return is_array($settings) ? $settings : [];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && is_array($base[$key] ?? null)) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @return array<int, string>
     */
    public function systemValue(string $key): mixed
    {
        $definition = $this->catalog()[$key] ?? null;
        if ($definition === null || ($definition['scope'] ?? null) !== 'system') {
            return null;
        }

        $setting = SystemSetting::query()->where('key', $key)->first();

        return $this->normalizeSystemValue($key, $setting?->value, $definition['default']);
    }

    /**
     * Get the system limits and force-disable flags relevant to center settings.
     *
     * @return array<string, mixed>
     */
    public function systemConstraints(): array
    {
        $defaults = $this->systemDefaults();

        return [
            'max_view_limit' => $defaults['max_view_limit'] ?? 10,
            'max_device_limit' => $defaults['max_device_limit'] ?? 3,
            'force_disable_extra_view_requests' => $defaults['force_disable_extra_view_requests'] ?? false,
            'force_disable_pdf_download' => $defaults['force_disable_pdf_download'] ?? false,
            'force_disable_guest_browsing' => $defaults['force_disable_guest_browsing'] ?? false,
        ];
    }

    /**
     * Get the default feature flags for a new center.
     *
     * @return array<string, bool>
     */
    public function defaultFeatures(): array
    {
        $defaults = $this->defaultCatalogValue('features', []);

        return is_array($defaults) ? $defaults : [];
    }

    /**
     * Get the resolved feature flags for a center.
     *
     * @return array<string, bool>
     */
    public function centerFeatures(Center $center): array
    {
        $raw = $this->rawCenterSettings($center);
        $features = is_array($raw['features'] ?? null) ? $raw['features'] : [];

        return $this->mergeRecursive($this->defaultFeatures(), $features);
    }

    public function isFeatureEnabled(Center $center, string $feature): bool
    {
        return (bool) ($this->centerFeatures($center)[$feature] ?? false);
    }

    public function centerAllowsGuestBrowsing(Center $center): bool
    {
        $constraints = $this->systemConstraints();

        if (($constraints['force_disable_guest_browsing'] ?? false) === true) {
            return false;
        }

        if (! $this->isFeatureEnabled($center, 'guest_browsing')) {
            return false;
        }

        return (bool) $center->allow_guest_browsing;
    }

    /**
     * Apply the effective guest-browsing policy to a center query.
     *
     * @param  Builder<Center>  $query
     * @return Builder<Center>
     */
    public function applyGuestBrowsingFilter(Builder $query): Builder
    {
        if (($this->systemConstraints()['force_disable_guest_browsing'] ?? false) === true) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('allow_guest_browsing', true)
            ->where(static function (Builder $query): void {
                $query->whereDoesntHave('setting')
                    ->orWhereHas('setting', static function (Builder $query): void {
                        $query->where(static function (Builder $query): void {
                            $query->whereNull('settings->features->guest_browsing')
                                ->orWhere('settings->features->guest_browsing', true);
                        });
                    });
            });
    }

    private function normalizeSystemValue(string $key, mixed $value, mixed $default): mixed
    {
        return match ($key) {
            'site_name' => is_array($value) ? $this->mergeRecursive($default, $value) : $default,
            'support_email' => is_array($value) && is_string($value['email'] ?? null) && $value['email'] !== '' ? $value['email'] : $default,
            'timezone' => is_array($value) && is_string($value['timezone'] ?? null) && $value['timezone'] !== '' ? $value['timezone'] : $default,
            'require_device_approval', 'attendance_required',
            'force_disable_extra_view_requests', 'force_disable_pdf_download', 'force_disable_guest_browsing' => is_array($value)
                ? (bool) ($value['enabled'] ?? $default)
                : $default,
            'max_view_limit' => is_array($value) && is_numeric($value['value'] ?? null) ? (int) $value['value'] : $default,
            'max_device_limit' => is_array($value) && is_numeric($value['value'] ?? null) ? (int) $value['value'] : $default,
            'whatsapp_bulk_settings' => is_array($value) ? $this->mergeRecursive($default, $value) : $default,
            default => $default,
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, mixed>
     */
    private function rulesFromDefinitions(array $definitions, string $prefix): array
    {
        $rules = [];

        foreach ($definitions as $key => $definition) {
            $path = $prefix.'.'.$key;
            $rules[$path] = array_merge(
                ['sometimes'],
                $definition['rules'] ?? $this->defaultRulesForType($definition['type'] ?? null),
            );

            $properties = $definition['properties'] ?? null;
            if (is_array($properties)) {
                $rules = array_merge($rules, $this->rulesFromDefinitions($properties, $path));
            }
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function defaultRulesForType(?string $type): array
    {
        return match ($type) {
            'boolean' => ['boolean'],
            'integer' => ['integer'],
            'string' => ['string'],
            default => ['array'],
        };
    }

    private function defaultCatalogValue(string $key, mixed $fallback): mixed
    {
        return $this->catalog()[$key]['default'] ?? $fallback;
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, array<int, string>>
     */
    private function groupDefinitions(array $definitions): array
    {
        $groups = [];

        foreach ($definitions as $key => $definition) {
            $group = is_string($definition['group'] ?? null) ? $definition['group'] : 'general';
            $groups[$group][] = $key;
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function applyCenterGovernance(Center $center, array $resolved): array
    {
        $constraints = $this->systemConstraints();

        if (isset($resolved['default_view_limit']) && is_numeric($resolved['default_view_limit'])) {
            $resolved['default_view_limit'] = min((int) $resolved['default_view_limit'], (int) $constraints['max_view_limit']);
        }

        if (isset($resolved['device_limit']) && is_numeric($resolved['device_limit'])) {
            $resolved['device_limit'] = min((int) $resolved['device_limit'], (int) $constraints['max_device_limit']);
        }

        if ($constraints['force_disable_extra_view_requests'] === true) {
            $resolved['allow_extra_view_requests'] = false;
        }

        if ($constraints['force_disable_pdf_download'] === true) {
            $resolved['pdf_download_permission'] = false;
        }

        if ($constraints['force_disable_guest_browsing'] === true) {
            $resolved['allow_guest_browsing'] = false;
        }

        $features = $this->centerFeatures($center);

        foreach ($this->centerSettingsCatalog() as $key => $definition) {
            $featureFlag = $definition['feature_flag'] ?? null;
            if (! is_string($featureFlag)) {
                continue;
            }

            if (($features[$featureFlag] ?? true) === true) {
                continue;
            }

            $resolved[$key] = $this->disabledValueForDefinition($definition);
        }

        $resolved['features'] = $features;

        return $resolved;
    }

    private function disabledValueForDefinition(array $definition): mixed
    {
        if (array_key_exists('default', $definition)) {
            return $definition['default'];
        }

        return match ($definition['type'] ?? null) {
            'boolean' => false,
            'integer' => null,
            'string' => null,
            default => [],
        };
    }
}
