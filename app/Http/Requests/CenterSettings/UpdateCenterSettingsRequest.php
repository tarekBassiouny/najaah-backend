<?php

declare(strict_types=1);

namespace App\Http\Requests\CenterSettings;

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
            'settings.pdf_download_permission' => ['sometimes', 'boolean'],
            'settings.device_limit' => ['sometimes', 'integer', 'min:1'],
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
                    'pdf_download_permission' => true,
                    'device_limit' => 1,
                    'branding' => [
                        'logo_url' => 'https://example.com/logo.png',
                        'primary_color' => '#000000',
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
                return;
            }

            $allowedKeys = [
                'default_view_limit',
                'allow_extra_view_requests',
                'pdf_download_permission',
                'device_limit',
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
