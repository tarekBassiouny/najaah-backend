<?php

declare(strict_types=1);

namespace App\Http\Requests\Centers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCenterRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:centers,slug'],
            'type' => ['required', 'integer'],
            'name_translations' => ['required', 'array'],
            'description_translations' => ['nullable', 'array'],
            'logo_url' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string'],
            'default_view_limit' => ['nullable', 'integer', 'min:0'],
            'allow_extra_view_requests' => ['boolean'],
            'pdf_download_permission' => ['boolean'],
            'device_limit' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
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
                'example' => ['en' => 'Center Name', 'ar' => 'اسم المركز'],
            ],
            'description_translations' => [
                'description' => 'Localized center description.',
                'example' => ['en' => 'Description'],
            ],
            'logo_url' => [
                'description' => 'Logo URL for the center.',
                'example' => 'https://example.com/logo.png',
            ],
            'primary_color' => [
                'description' => 'Primary branding color.',
                'example' => '#000000',
            ],
            'default_view_limit' => [
                'description' => 'Default view limit for videos.',
                'example' => 3,
            ],
            'allow_extra_view_requests' => [
                'description' => 'Whether students can request extra views.',
                'example' => true,
            ],
            'pdf_download_permission' => [
                'description' => 'Whether PDF downloads are allowed by default.',
                'example' => false,
            ],
            'device_limit' => [
                'description' => 'Maximum active devices per student.',
                'example' => 1,
            ],
            'settings' => [
                'description' => 'Optional center settings payload.',
                'example' => ['pdf_download_permission' => true],
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
