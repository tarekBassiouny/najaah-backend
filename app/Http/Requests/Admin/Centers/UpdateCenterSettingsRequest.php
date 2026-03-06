<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

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
        return [
            'settings' => ['required', 'array'],
            'settings.default_view_limit' => ['sometimes', 'integer', 'min:0'],
            'settings.allow_extra_view_requests' => ['sometimes', 'boolean'],
            'settings.requires_video_approval' => ['sometimes', 'boolean'],
            'settings.video_code_expiry_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'settings.pdf_download_permission' => ['sometimes', 'boolean'],
            'settings.device_limit' => ['sometimes', 'integer', 'min:1'],
            'settings.whatsapp_bulk_settings' => ['sometimes', 'array'],
            'settings.whatsapp_bulk_settings.delay_seconds' => ['sometimes', 'integer', 'min:0'],
            'settings.whatsapp_bulk_settings.batch_size' => ['sometimes', 'integer', 'min:1'],
            'settings.whatsapp_bulk_settings.batch_pause_seconds' => ['sometimes', 'integer', 'min:0'],
            'settings.whatsapp_bulk_settings.max_retries' => ['sometimes', 'integer', 'min:0'],
            'settings.whatsapp_bulk_settings.max_failures_before_pause' => ['sometimes', 'integer', 'min:1'],
            'settings.branding' => ['sometimes', 'array'],
            'settings.branding.logo_url' => ['sometimes', 'nullable', 'string'],
            'settings.branding.primary_color' => ['sometimes', 'nullable', 'string'],
        ];
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
                    'whatsapp_bulk_settings' => [
                        'delay_seconds' => 3,
                        'batch_size' => 50,
                        'batch_pause_seconds' => 60,
                        'max_retries' => 2,
                        'max_failures_before_pause' => 10,
                    ],
                    'branding' => [
                        'logo_url' => 'https://example.com/logo.png',
                        'primary_color' => '#000000',
                    ],
                ],
            ],
            'settings.default_view_limit' => [
                'description' => 'Default view limit for videos.',
                'example' => 3,
            ],
            'settings.allow_extra_view_requests' => [
                'description' => 'Whether students can request extra views.',
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
                'description' => 'Whether PDF downloads are allowed.',
                'example' => true,
            ],
            'settings.device_limit' => [
                'description' => 'Maximum active devices per student.',
                'example' => 1,
            ],
            'settings.whatsapp_bulk_settings' => [
                'description' => 'Configuration for queue-based bulk WhatsApp sending.',
                'example' => [
                    'delay_seconds' => 3,
                    'batch_size' => 50,
                    'batch_pause_seconds' => 60,
                    'max_retries' => 2,
                    'max_failures_before_pause' => 10,
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $settings = $this->input('settings');
            if (! is_array($settings)) {
                return;
            }

            $allowedKeys = [
                'default_view_limit',
                'allow_extra_view_requests',
                'requires_video_approval',
                'video_code_expiry_days',
                'pdf_download_permission',
                'device_limit',
                'whatsapp_bulk_settings',
                'branding',
            ];

            $invalidKeys = array_diff(array_keys($settings), $allowedKeys);
            if (! empty($invalidKeys)) {
                $validator->errors()->add('settings', 'Unsupported settings: '.implode(', ', $invalidKeys));
            }

            if (isset($settings['branding']) && is_array($settings['branding'])) {
                $brandingAllowed = ['logo_url', 'primary_color'];
                $invalidBranding = array_diff(array_keys($settings['branding']), $brandingAllowed);

                if (! empty($invalidBranding)) {
                    $validator->errors()->add('settings.branding', 'Unsupported branding settings: '.implode(', ', $invalidBranding));
                }
            }

            if (isset($settings['whatsapp_bulk_settings']) && is_array($settings['whatsapp_bulk_settings'])) {
                $bulkAllowed = [
                    'delay_seconds',
                    'batch_size',
                    'batch_pause_seconds',
                    'max_retries',
                    'max_failures_before_pause',
                ];
                $invalidBulk = array_diff(array_keys($settings['whatsapp_bulk_settings']), $bulkAllowed);

                if (! empty($invalidBulk)) {
                    $validator->errors()->add('settings.whatsapp_bulk_settings', 'Unsupported WhatsApp bulk settings: '.implode(', ', $invalidBulk));
                }
            }
        });
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
