<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Center;
use App\Models\SystemSetting;

class PolicySettingsService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        return [
            'default_view_limit' => [
                'scope' => 'center',
                'type' => 'integer',
                'storage' => 'center_settings.settings.default_view_limit',
                'fallback' => 'centers.default_view_limit',
                'default' => 2,
            ],
            'allow_extra_view_requests' => [
                'scope' => 'center',
                'type' => 'boolean',
                'storage' => 'center_settings.settings.allow_extra_view_requests',
                'fallback' => 'centers.allow_extra_view_requests',
                'default' => true,
            ],
            'pdf_download_permission' => [
                'scope' => 'center',
                'type' => 'boolean',
                'storage' => 'center_settings.settings.pdf_download_permission',
                'fallback' => 'centers.pdf_download_permission',
                'default' => false,
            ],
            'device_limit' => [
                'scope' => 'center',
                'type' => 'integer',
                'storage' => 'center_settings.settings.device_limit',
                'fallback' => 'centers.device_limit',
                'default' => 1,
            ],
            'branding' => [
                'scope' => 'center',
                'type' => 'object',
                'storage' => 'center_settings.settings.branding',
                'fallback' => 'centers.logo_url,centers.primary_color',
                'default' => [],
            ],
            'education_profile' => [
                'scope' => 'center',
                'type' => 'object',
                'storage' => 'center_settings.settings.education_profile',
                'default' => [
                    'enable_grade' => true,
                    'enable_school' => true,
                    'enable_college' => true,
                    'require_grade' => false,
                    'require_school' => false,
                    'require_college' => false,
                ],
            ],
            'timezone' => [
                'scope' => 'system',
                'type' => 'string',
                'storage' => 'system_settings.key=timezone',
                'default' => 'UTC',
            ],
            'support_email' => [
                'scope' => 'system',
                'type' => 'string',
                'storage' => 'system_settings.key=support_email',
                'default' => 'support@example.com',
            ],
            'require_device_approval' => [
                'scope' => 'system',
                'type' => 'boolean',
                'storage' => 'system_settings.key=require_device_approval',
                'default' => false,
            ],
            'attendance_required' => [
                'scope' => 'system',
                'type' => 'boolean',
                'storage' => 'system_settings.key=attendance_required',
                'default' => false,
            ],
        ];
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
            'branding' => $branding,
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => true,
                'enable_college' => true,
                'require_grade' => false,
                'require_school' => false,
                'require_college' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function systemDefaults(): array
    {
        $defaults = [];
        $settings = SystemSetting::query()
            ->whereIn('key', $this->systemKeys())
            ->get()
            ->keyBy('key');

        foreach ($this->catalog() as $key => $definition) {
            if (($definition['scope'] ?? null) !== 'system') {
                continue;
            }

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
        return $this->mergeRecursive(
            $this->systemDefaults(),
            $this->mergeRecursive($this->centerDefaults($center), $this->rawCenterSettings($center)),
        );
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
    private function systemKeys(): array
    {
        return array_keys(array_filter(
            $this->catalog(),
            static fn (array $definition): bool => ($definition['scope'] ?? null) === 'system'
        ));
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    private function normalizeSystemValue(string $key, ?array $value, mixed $default): mixed
    {
        return match ($key) {
            'support_email' => is_string($value['email'] ?? null) && $value['email'] !== '' ? $value['email'] : $default,
            'timezone' => is_string($value['timezone'] ?? null) && $value['timezone'] !== '' ? $value['timezone'] : $default,
            'require_device_approval', 'attendance_required' => (bool) ($value['enabled'] ?? $default),
            default => $default,
        };
    }
}
