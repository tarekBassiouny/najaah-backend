<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Http\Resources\Admin\Centers\CenterSettingResource;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\User;
use App\Services\AI\Contracts\AIIntegrationServiceInterface;
use App\Services\Branding\CenterLogoUrlResolver;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use Illuminate\Support\Facades\DB;

class CenterSettingsPageService
{
    public function __construct(
        private readonly CenterSettingsServiceInterface $centerSettingsService,
        private readonly PolicySettingsService $policySettingsService,
        private readonly AIIntegrationServiceInterface $aiIntegrationService,
        private readonly CenterScopeService $centerScopeService,
        private readonly CenterLogoUrlResolver $logoUrlResolver
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function show(User $actor, Center $center): array
    {
        $setting = $this->centerSettingsService->get($actor, $center);
        $center->loadMissing('setting');

        return $this->buildPayload($actor, $center, $setting);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(User $actor, Center $center, array $payload): array
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $center);

        return DB::transaction(function () use ($actor, $center, $payload): array {
            /** @var array<string, mixed> $settings */
            $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
            /** @var array<string, bool> $features */
            $features = is_array($payload['features'] ?? null) ? $payload['features'] : [];
            /** @var array<string, array<string, mixed>> $providers */
            $providers = is_array(data_get($payload, 'ai.providers')) ? data_get($payload, 'ai.providers') : [];

            $setting = $this->centerSettingsService->get($actor, $center);

            if ($settings !== []) {
                $setting = $this->centerSettingsService->update($actor, $center, $settings);
            }

            if ($features !== []) {
                $setting = $this->centerSettingsService->updateFeatures($actor, $center, $features);
            }

            foreach ($providers as $providerKey => $providerPayload) {
                if (! is_array($providerPayload)) {
                    continue;
                }

                $this->aiIntegrationService->updateCenterProvider($center, $providerKey, $providerPayload, $actor);
            }

            $center->refresh()->loadMissing('setting');

            return $this->buildPayload($actor, $center, $setting->fresh() ?? $setting);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(User $actor, Center $center, CenterSetting $setting): array
    {
        $isSystemAdmin = $this->centerScopeService->isSystemSuperAdmin($actor);
        $payload = (new CenterSettingResource($setting))->resolve();
        $payload['settings'] = $this->resolveBranding($payload['settings']);

        $resolvedSettings = $this->policySettingsService->resolveCenterPolicy($center);
        $resolvedSettings['branding'] = $this->resolveBranding($resolvedSettings['branding'] ?? []);

        $features = $this->policySettingsService->centerFeatures($center);
        $providers = $this->aiIntegrationService->listCenterProviders($center);

        $base = array_merge($payload, [
            'resolved_settings' => $resolvedSettings,
            'system_constraints' => $this->policySettingsService->systemConstraints(),
            'page' => [
                'type' => $isSystemAdmin ? 'system_admin_center_settings' : 'center_admin_settings',
                'actor_scope' => $isSystemAdmin ? 'system' : 'center',
                'editable' => [
                    'settings' => $this->policySettingsService->centerSettingKeys(),
                    'features' => $isSystemAdmin ? $this->policySettingsService->featureFlagKeys() : [],
                    'ai' => [
                        'providers' => $this->providerEditableFields($providers, $isSystemAdmin),
                    ],
                ],
            ],
            'sections' => [
                'settings' => [
                    'groups' => $this->groupSettings($payload['settings']),
                    'resolved_groups' => $this->groupSettings($resolvedSettings),
                ],
                'feature_groups' => $this->policySettingsService->featureGroups($features),
                'ai' => [
                    'feature_enabled' => (bool) ($features['ai_content'] ?? true),
                    'providers' => $this->transformProvidersForActor($providers, $isSystemAdmin),
                ],
            ],
        ]);

        if ($isSystemAdmin) {
            $base['system_defaults'] = $this->policySettingsService->systemDefaults();
            $base['features'] = $features;
            $base['catalog'] = $this->policySettingsService->catalog();
            $base['sections']['features'] = [
                'values' => $features,
            ];

            return $base;
        }

        $base['summaries'] = $this->buildCenterAdminSummaries($features, $providers);

        return $base;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, array<string, mixed>>
     */
    private function groupSettings(array $settings): array
    {
        $groups = [];

        foreach ($this->policySettingsService->centerSettingsCatalog() as $key => $definition) {
            $group = is_string($definition['group'] ?? null) ? $definition['group'] : 'general';

            if (! array_key_exists($key, $settings)) {
                continue;
            }

            $groups[$group][$key] = $settings[$key];
        }

        return $groups;
    }

    /**
     * @param  array<int, array<string, mixed>>  $providers
     * @return array<string, array<int, string>>
     */
    private function providerEditableFields(array $providers, bool $isSystemAdmin): array
    {
        $editable = [];

        foreach ($providers as $provider) {
            $key = is_string($provider['key'] ?? null) ? $provider['key'] : null;
            if ($key === null) {
                continue;
            }

            $editable[$key] = $isSystemAdmin
                ? ['is_enabled', 'allowed_models', 'default_model', 'limits']
                : ['default_model'];
        }

        return $editable;
    }

    /**
     * @param  array<int, array<string, mixed>>  $providers
     * @return array<int, array<string, mixed>>
     */
    private function transformProvidersForActor(array $providers, bool $isSystemAdmin): array
    {
        if ($isSystemAdmin) {
            return array_map(function (array $provider): array {
                $provider['editable_fields'] = ['is_enabled', 'allowed_models', 'default_model', 'limits'];

                return $provider;
            }, $providers);
        }

        return array_map(static function (array $provider): array {
            return [
                'key' => $provider['key'] ?? null,
                'label' => $provider['label'] ?? null,
                'enabled' => (bool) ($provider['enabled'] ?? false),
                'configured' => (bool) ($provider['configured'] ?? false),
                'default_model' => $provider['default_model'] ?? null,
                'models' => $provider['models'] ?? [],
                'managed_by' => 'platform',
                'editable_fields' => ['default_model'],
            ];
        }, $providers);
    }

    /**
     * @param  array<string, bool>  $features
     * @param  array<int, array<string, mixed>>  $providers
     * @return array<int, array<string, string>>
     */
    private function buildCenterAdminSummaries(array $features, array $providers): array
    {
        $summaries = [];

        if (($features['ai_content'] ?? true) !== true) {
            $summaries[] = [
                'type' => 'info',
                'title' => 'AI content is disabled',
                'message' => 'AI tools are managed by platform admin for this center.',
            ];
        }

        if (($features['guest_browsing'] ?? true) !== true) {
            $summaries[] = [
                'type' => 'info',
                'title' => 'Guest browsing is disabled',
                'message' => 'Guest browsing is managed by platform policy for this center.',
            ];
        }

        if (($features['pdf_downloads'] ?? true) !== true) {
            $summaries[] = [
                'type' => 'info',
                'title' => 'PDF downloads are disabled',
                'message' => 'PDF download availability is managed by platform admin.',
            ];
        }

        $configuredProvider = collect($providers)->first(static fn (array $provider): bool => (bool) ($provider['enabled'] ?? false) && (bool) ($provider['configured'] ?? false));
        if (is_array($configuredProvider)) {
            $label = (string) ($configuredProvider['label'] ?? $configuredProvider['key'] ?? 'AI provider');
            $summaries[] = [
                'type' => 'info',
                'title' => 'AI provider managed by platform',
                'message' => sprintf('%s is configured for your center. Provider availability and limits are managed by platform admin.', $label),
            ];
        }

        return $summaries;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveBranding(array $data): array
    {
        if (! isset($data['branding']) || ! is_array($data['branding'])) {
            return $data;
        }

        $logo = $data['branding']['logo_url'] ?? null;
        if (is_string($logo) && $logo !== '') {
            $data['branding']['logo_url'] = $this->logoUrlResolver->resolve($logo);
        }

        return $data;
    }
}
