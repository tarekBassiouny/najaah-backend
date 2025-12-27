<?php

declare(strict_types=1);

namespace App\Http\Requests\Centers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

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
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:centers,slug'],
            'type' => ['nullable', 'integer'],
            'name' => ['required_without:name_translations', 'string', 'max:255'],
            'name_translations' => ['required_without:name', 'array'],
            'description_translations' => ['nullable', 'array'],
            'logo_url' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string'],
            'onboarding_status' => ['nullable', 'string'],
            'branding_metadata' => ['nullable', 'array'],
            'storage_driver' => ['nullable', 'string'],
            'storage_root' => ['nullable', 'string'],
            'default_view_limit' => ['nullable', 'integer', 'min:0'],
            'allow_extra_view_requests' => ['boolean'],
            'pdf_download_permission' => ['boolean'],
            'device_limit' => ['nullable', 'integer', 'min:1'],
            'settings' => ['nullable', 'array'],
            'owner_user_id' => ['required_without:owner', 'nullable', 'integer', 'exists:users,id'],
            'owner' => ['required_without:owner_user_id', 'nullable', 'array'],
            'owner.name' => ['required_without:owner_user_id', 'string', 'max:255'],
            'owner.email' => ['required_without:owner_user_id', 'email', 'max:255', 'unique:users,email'],
            'owner.phone' => ['nullable', 'string', 'max:50'],
            'owner_role' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (int) ($this->input('type') ?? 0);

            if ($type !== 1) {
                return;
            }

            $logo = $this->input('logo_url');
            $primaryColor = $this->input('primary_color');
            $branding = $this->input('branding_metadata');

            $brandingLogo = is_array($branding) ? Arr::get($branding, 'logo_url') : null;
            $brandingColor = is_array($branding) ? Arr::get($branding, 'primary_color') : null;

            if (! is_string($logo) || $logo === '') {
                if (! is_string($brandingLogo) || $brandingLogo === '') {
                    $validator->errors()->add('logo_url', 'Branding logo is required for branded centers.');
                }
            }

            if (! is_string($primaryColor) || $primaryColor === '') {
                if (! is_string($brandingColor) || $brandingColor === '') {
                    $validator->errors()->add('primary_color', 'Primary color is required for branded centers.');
                }
            }
        });
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
            'name' => [
                'description' => 'Center name when translations are not provided.',
                'example' => 'Center Name',
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
            'owner_user_id' => [
                'description' => 'Existing user ID to assign as the owner.',
                'example' => 10,
            ],
            'owner' => [
                'description' => 'Owner details when creating a new owner user.',
                'example' => [
                    'name' => 'Owner Name',
                    'email' => 'owner@example.com',
                    'phone' => '+1234567890',
                ],
            ],
            'owner.name' => [
                'description' => 'Owner name.',
                'example' => 'Owner Name',
            ],
            'owner.email' => [
                'description' => 'Owner email address.',
                'example' => 'owner@example.com',
            ],
            'owner.phone' => [
                'description' => 'Owner phone number.',
                'example' => '+1234567890',
            ],
            'owner_role' => [
                'description' => 'Optional role name to assign to the owner.',
                'example' => 'center_owner',
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
