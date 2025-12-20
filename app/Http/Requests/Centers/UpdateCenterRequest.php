<?php

declare(strict_types=1);

namespace App\Http\Requests\Centers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCenterRequest extends FormRequest
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
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash'],
            'type' => ['sometimes', 'integer'],
            'name_translations' => ['sometimes', 'array'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'logo_url' => ['sometimes', 'nullable', 'string'],
            'primary_color' => ['sometimes', 'nullable', 'string'],
            'default_view_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'allow_extra_view_requests' => ['sometimes', 'boolean'],
            'pdf_download_permission' => ['sometimes', 'boolean'],
            'device_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'slug' => [
                'description' => 'Unique, immutable center slug.',
                'example' => 'center-01',
            ],
            'type' => [
                'description' => 'Center type identifier.',
                'example' => 0,
            ],
            'name_translations' => [
                'description' => 'Localized center name.',
                'example' => ['en' => 'Updated Name'],
            ],
            'description_translations' => [
                'description' => 'Localized description.',
                'example' => ['en' => 'Updated description'],
            ],
            'logo_url' => [
                'description' => 'Logo URL.',
                'example' => 'https://example.com/logo.png',
            ],
            'primary_color' => [
                'description' => 'Primary branding color.',
                'example' => '#123456',
            ],
            'default_view_limit' => [
                'description' => 'Default view limit for videos.',
                'example' => 5,
            ],
            'allow_extra_view_requests' => [
                'description' => 'Whether extra view requests are allowed.',
                'example' => true,
            ],
            'pdf_download_permission' => [
                'description' => 'Whether PDF downloads are allowed by default.',
                'example' => true,
            ],
            'device_limit' => [
                'description' => 'Maximum active devices per student.',
                'example' => 2,
            ],
            'settings' => [
                'description' => 'Optional settings overrides.',
                'example' => ['pdf_download_permission' => false],
            ],
        ];
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
