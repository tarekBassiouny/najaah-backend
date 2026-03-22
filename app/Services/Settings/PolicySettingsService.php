<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * Build feature group definitions from catalog metadata.
     *
     * @param  array<string, bool>  $features
     * @return array<string, array<string, mixed>>
     */
    public function featureGroups(array $features): array
    {
        $groups = [];
        $template = [
            'feature_flag' => null,
            'flag_enabled' => false,
            'center_settings' => [],
            'system_limits' => [],
            'system_overrides' => [],
            'depends_on' => null,
        ];

        foreach ($this->featureFlagCatalog() as $flagKey => $flagDef) {
            $group = $flagDef['feature_group'] ?? null;
            if (! is_string($group)) {
                continue;
            }

            $groups[$group] ??= $template;
            $groups[$group]['feature_flag'] = $flagKey;
            $groups[$group]['flag_enabled'] = (bool) ($features[$flagKey] ?? false);
        }

        foreach ($this->centerSettingsCatalog() as $key => $definition) {
            $group = $definition['feature_group'] ?? null;
            if (! is_string($group)) {
                continue;
            }

            $groups[$group] ??= $template;
            $groups[$group]['center_settings'][] = $key;

            $dependsOn = $definition['depends_on'] ?? null;
            if (is_string($dependsOn)) {
                $groups[$group]['depends_on'] = $dependsOn;
            }
        }

        foreach ($this->systemSettingsCatalog() as $key => $definition) {
            $group = $definition['feature_group'] ?? null;
            if (! is_string($group)) {
                continue;
            }

            $groups[$group] ??= $template;

            $settingGroup = $definition['group'] ?? null;
            if ($settingGroup === 'limits') {
                $groups[$group]['system_limits'][] = $key;
            } elseif ($settingGroup === 'overrides') {
                $groups[$group]['system_overrides'][] = $key;
            }
        }

        return $groups;
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
            $defaults[$key] = $this->normalizeSystemValue($setting?->value, $definition);
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

    public function systemValue(string $key): mixed
    {
        $definition = $this->catalog()[$key] ?? null;
        if ($definition === null || ($definition['scope'] ?? null) !== 'system') {
            return null;
        }

        $setting = SystemSetting::query()->where('key', $key)->first();

        return $this->normalizeSystemValue($setting?->value, $definition);
    }

    /**
     * Get the system limits and force-disable flags relevant to center settings.
     *
     * @return array<string, mixed>
     */
    public function systemConstraints(): array
    {
        $defaults = $this->systemDefaults();
        $constraints = [];

        foreach ($this->systemSettingsCatalog() as $key => $definition) {
            $group = $definition['group'] ?? null;
            if ($group === 'limits' || $group === 'overrides') {
                $constraints[$key] = $defaults[$key] ?? $definition['default'];
            }
        }

        return $constraints;
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

    /**
     * Apply system constraints (ceilings, overrides, feature flags) to resolved settings.
     *
     * Used by SettingsResolverService to avoid duplicating governance logic.
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    public function applyGovernanceConstraints(array $resolved, ?Center $center): array
    {
        $constraints = $this->systemConstraints();

        foreach ($this->centerSettingsCatalog() as $key => $definition) {
            if (! array_key_exists($key, $resolved)) {
                continue;
            }

            $systemLimit = $definition['system_limit'] ?? null;
            if (is_string($systemLimit) && isset($constraints[$systemLimit]) && is_numeric($resolved[$key])) {
                $resolved[$key] = min((int) $resolved[$key], (int) $constraints[$systemLimit]);
            }

            $systemOverride = $definition['system_override'] ?? null;
            if (is_string($systemOverride) && ($constraints[$systemOverride] ?? false) === true) {
                $resolved[$key] = $this->disabledValueForDefinition($definition);
            }
        }

        if ($center instanceof Center) {
            $features = $this->centerFeatures($center);

            foreach ($this->centerSettingsCatalog() as $key => $definition) {
                $featureFlag = $definition['feature_flag'] ?? null;
                if (! is_string($featureFlag)) {
                    continue;
                }

                if (! array_key_exists($key, $resolved)) {
                    continue;
                }

                if (($features[$featureFlag] ?? true) !== true) {
                    $resolved[$key] = $this->disabledValueForDefinition($definition);
                }
            }
        }

        return $resolved;
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
     * Larastan commonly infers relation subqueries as Builder<Model> in whereHas closures,
     * while direct Center queries stay Builder<Center>. Support both call sites here.
     *
     * @param  Builder<Center>|Builder<Model>  $query
     * @return Builder<Center>|Builder<Model>
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

    /**
     * @param  array<string, mixed>  $definition
     */
    private function normalizeSystemValue(mixed $value, array $definition): mixed
    {
        $default = $definition['default'];
        $valueKey = $definition['value_key'] ?? null;
        $type = $definition['type'] ?? 'string';

        if (! is_array($value)) {
            return $default;
        }

        // Object types without a value_key → merge recursively
        if ($valueKey === null) {
            return $this->mergeRecursive($default, $value);
        }

        $extracted = $value[$valueKey] ?? null;

        return match ($type) {
            'boolean' => (bool) ($extracted ?? $default),
            'integer' => is_numeric($extracted) ? (int) $extracted : $default,
            'string' => is_string($extracted) && $extracted !== '' ? $extracted : $default,
            default => $extracted ?? $default,
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
        $features = $this->centerFeatures($center);

        foreach ($this->centerSettingsCatalog() as $key => $definition) {
            if (! array_key_exists($key, $resolved)) {
                continue;
            }

            // System limit ceiling: min(resolved, limit)
            $systemLimit = $definition['system_limit'] ?? null;
            if (is_string($systemLimit) && isset($constraints[$systemLimit]) && is_numeric($resolved[$key])) {
                $resolved[$key] = min((int) $resolved[$key], (int) $constraints[$systemLimit]);
            }

            // System override: force-disable when override is true
            $systemOverride = $definition['system_override'] ?? null;
            if (is_string($systemOverride) && ($constraints[$systemOverride] ?? false) === true) {
                $resolved[$key] = $this->disabledValueForDefinition($definition);
            }

            // Feature flag: disable when flag is off
            $featureFlag = $definition['feature_flag'] ?? null;
            if (is_string($featureFlag) && ($features[$featureFlag] ?? true) !== true) {
                $resolved[$key] = $this->disabledValueForDefinition($definition);
            }

            // Dependency: disable when the depended-on setting is disabled
            $dependsOn = $definition['depends_on'] ?? null;
            if (is_string($dependsOn) && array_key_exists($dependsOn, $resolved)) {
                $depDefinition = $this->catalog()[$dependsOn] ?? null;
                if (is_array($depDefinition) && $resolved[$dependsOn] === $this->disabledValueForDefinition($depDefinition)) {
                    $resolved[$key] = $this->disabledValueForDefinition($definition);
                }
            }
        }

        $resolved['features'] = $features;

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function disabledValueForDefinition(array $definition): mixed
    {
        return match ($definition['type'] ?? null) {
            'boolean' => false,
            'integer' => array_key_exists('default', $definition) ? $definition['default'] : 0,
            'string' => array_key_exists('default', $definition) ? $definition['default'] : null,
            default => [],
        };
    }
}
