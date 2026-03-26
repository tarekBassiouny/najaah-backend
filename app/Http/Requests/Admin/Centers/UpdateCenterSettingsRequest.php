<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Enums\AIProvider;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\PolicySettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCenterSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'settings' => ['sometimes', 'array'],
                'features' => ['sometimes', 'array'],
                'ai' => ['sometimes', 'array'],
                'ai.providers' => ['sometimes', 'array'],
            ],
            $this->policySettingsService()->centerSettingRules('settings'),
            $this->policySettingsService()->featureFlagRules('features'),
            $this->aiProviderRules()
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'settings' => [
                'description' => 'Center settings payload.',
                'example' => [
                    'default_view_limit' => 3,
                    'allow_extra_view_requests' => true,
                    'requires_video_approval' => true,
                    'video_code_expiry_days' => 30,
                    'pdf_download_permission' => true,
                    'device_limit' => 1,
                    'allow_guest_browsing' => false,
                    'education_profile' => [
                        'enable_grade' => true,
                        'enable_school' => true,
                        'enable_college' => true,
                        'enable_parent_phone' => true,
                        'require_grade' => false,
                        'require_school' => false,
                        'require_college' => false,
                        'require_parent_phone' => false,
                    ],
                    'branding' => [
                        'logo_url' => 'https://example.com/logo.png',
                        'primary_color' => '#000000',
                    ],
                ],
            ],
            'settings.default_view_limit' => [
                'description' => 'Default view limit for videos. Must not exceed system max_view_limit.',
                'example' => 3,
            ],
            'settings.allow_extra_view_requests' => [
                'description' => 'Whether students can request extra views. May be force-disabled by system admin.',
                'example' => true,
            ],
            'settings.requires_video_approval' => [
                'description' => 'Whether videos require approval code redemption by default.',
                'example' => true,
            ],
            'settings.video_code_expiry_days' => [
                'description' => 'Optional expiry period in days for generated video access codes.',
                'example' => 30,
            ],
            'settings.pdf_download_permission' => [
                'description' => 'Whether PDF downloads are allowed. May be force-disabled by system admin.',
                'example' => true,
            ],
            'settings.device_limit' => [
                'description' => 'Maximum active devices per student. Must not exceed system max_device_limit.',
                'example' => 1,
            ],
            'settings.allow_guest_browsing' => [
                'description' => "Whether guest users can browse this center's courses without authentication. May be force-disabled by system admin.",
                'example' => false,
            ],
            'settings.education_profile' => [
                'description' => 'Education profile module configuration for student-facing education fields.',
                'example' => [
                    'enable_grade' => true,
                    'enable_school' => true,
                    'enable_college' => false,
                    'enable_parent_phone' => true,
                    'require_grade' => true,
                    'require_school' => false,
                    'require_college' => false,
                    'require_parent_phone' => true,
                ],
            ],
            'settings.branding' => [
                'description' => 'Branding settings payload.',
                'example' => [
                    'logo_url' => 'https://example.com/logo.png',
                    'primary_color' => '#000000',
                ],
            ],
            'settings.branding.logo_url' => [
                'description' => 'Logo URL.',
                'example' => 'https://example.com/logo.png',
            ],
            'settings.branding.primary_color' => [
                'description' => 'Primary branding color.',
                'example' => '#000000',
            ],
            'features' => [
                'description' => 'System-managed feature flags for this center. System admin only.',
                'example' => [
                    'ai_content' => true,
                    'guest_browsing' => true,
                ],
            ],
            'ai.providers' => [
                'description' => 'Per-provider AI settings for this center. System admin can edit all fields, center admin can only update default_model.',
                'example' => [
                    'openai' => [
                        'is_enabled' => true,
                        'allowed_models' => ['gpt-4o-mini'],
                        'default_model' => 'gpt-4o-mini',
                        'limits' => [
                            'daily_job_limit' => 200,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $settings = $this->input('settings');
            if (! is_array($settings)) {
                $settings = [];
            }

            $policySettingsService = $this->policySettingsService();
            $allowedKeys = $policySettingsService->centerSettingKeys();

            $invalidKeys = array_diff(array_keys($settings), $allowedKeys);
            if (! empty($invalidKeys)) {
                $validator->errors()->add('settings', __('settings.validation.unsupported_settings', [
                    'keys' => implode(', ', $invalidKeys),
                ]));
            }

            foreach ($settings as $key => $value) {
                if (! is_array($value)) {
                    continue;
                }

                $nestedKeys = $policySettingsService->nestedKeysFor($key);
                if ($nestedKeys === []) {
                    continue;
                }

                $invalidNestedKeys = array_diff(array_keys($value), $nestedKeys);
                if ($invalidNestedKeys === []) {
                    continue;
                }

                $validator->errors()->add(
                    'settings.'.$key,
                    __('settings.validation.unsupported_nested_settings', [
                        'setting' => str_replace('_', ' ', $key),
                        'keys' => implode(', ', $invalidNestedKeys),
                    ])
                );
            }

            $features = $this->input('features');
            if (is_array($features)) {
                $allowedFeatureKeys = $policySettingsService->featureFlagKeys();
                $invalidFeatureKeys = array_diff(array_keys($features), $allowedFeatureKeys);
                if ($invalidFeatureKeys !== []) {
                    $validator->errors()->add('features', __('settings.validation.unsupported_feature_flags', [
                        'keys' => implode(', ', $invalidFeatureKeys),
                    ]));
                }

                if (! $this->isSystemAdmin()) {
                    $validator->errors()->add('features', __('settings.validation.system_admin_only_feature_flags'));
                }
            }

            $providers = $this->input('ai.providers');
            if (is_array($providers)) {
                $invalidProviders = array_diff(array_keys($providers), AIProvider::values());
                if ($invalidProviders !== []) {
                    $validator->errors()->add('ai.providers', __('settings.validation.unsupported_ai_providers', [
                        'keys' => implode(', ', $invalidProviders),
                    ]));
                }

                foreach ($providers as $providerKey => $providerPayload) {
                    if (! is_array($providerPayload)) {
                        continue;
                    }

                    $allowedProviderKeys = $this->isSystemAdmin()
                        ? ['is_enabled', 'allowed_models', 'default_model', 'limits']
                        : ['default_model'];

                    $invalidProviderKeys = array_diff(array_keys($providerPayload), $allowedProviderKeys);
                    if ($invalidProviderKeys !== []) {
                        $validator->errors()->add(
                            'ai.providers.'.$providerKey,
                            __('settings.validation.unsupported_ai_provider_settings', [
                                'keys' => implode(', ', $invalidProviderKeys),
                            ])
                        );
                    }

                    $limits = $providerPayload['limits'] ?? null;
                    if (is_array($limits)) {
                        $allowedLimitKeys = [
                            'daily_job_limit',
                            'monthly_job_limit',
                            'daily_token_limit',
                            'monthly_token_limit',
                            'max_input_chars',
                            'max_output_chars',
                            'max_concurrent_jobs',
                            'default_output_token_estimate',
                        ];

                        $invalidLimitKeys = array_diff(array_keys($limits), $allowedLimitKeys);
                        if ($invalidLimitKeys !== []) {
                            $validator->errors()->add(
                                'ai.providers.'.$providerKey.'.limits',
                                __('settings.validation.unsupported_ai_limit_keys', [
                                    'keys' => implode(', ', $invalidLimitKeys),
                                ])
                            );
                        }

                        if (! $this->isSystemAdmin()) {
                            $validator->errors()->add('ai.providers.'.$providerKey.'.limits', __('settings.validation.system_admin_only_ai_limits'));
                        }
                    }
                }
            }

            if ($settings === [] && ! is_array($this->input('features')) && ! is_array($this->input('ai.providers'))) {
                $validator->errors()->add('settings', __('settings.validation.settings_payload_required'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function aiProviderRules(): array
    {
        return [
            'ai.providers.*' => ['sometimes', 'array'],
            'ai.providers.*.is_enabled' => ['sometimes', 'boolean'],
            'ai.providers.*.allowed_models' => ['sometimes', 'nullable', 'array'],
            'ai.providers.*.allowed_models.*' => ['required_with:ai.providers.*.allowed_models', 'string', 'max:120'],
            'ai.providers.*.default_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'ai.providers.*.limits' => ['sometimes', 'array'],
            'ai.providers.*.limits.daily_job_limit' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.monthly_job_limit' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.daily_token_limit' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.monthly_token_limit' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.max_input_chars' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.max_output_chars' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.max_concurrent_jobs' => ['sometimes', 'integer', 'min:0'],
            'ai.providers.*.limits.default_output_token_estimate' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    private function policySettingsService(): PolicySettingsService
    {
        return app(PolicySettingsService::class);
    }

    private function isSystemAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && app(CenterScopeService::class)->isSystemSuperAdmin($user);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
