<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Models\Center;
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
            'type' => ['required', 'string', 'in:branded,unbranded'],
            'tier' => ['sometimes', 'string', 'in:standard,premium,vip'],
            'is_featured' => ['sometimes', 'boolean'],
            'name_translations' => ['required', 'array', 'min:1'],
            'name_translations.*' => ['string', 'max:255'],
            'branding_metadata' => ['required_if:type,branded', 'array'],
            'branding_metadata.primary_color' => ['required_if:type,branded', 'string'],
            'admin' => ['required', 'array'],
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['prohibited'],
            'description_translations' => ['prohibited'],
            'description_translations.*' => ['prohibited'],
            'logo_url' => ['prohibited'],
            'primary_color' => ['prohibited'],
            'default_view_limit' => ['prohibited'],
            'allow_extra_view_requests' => ['prohibited'],
            'pdf_download_permission' => ['prohibited'],
            'device_limit' => ['prohibited'],
            'settings' => ['prohibited'],
            'owner_user_id' => ['prohibited'],
            'owner' => ['prohibited'],
            'owner_role' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        $type = $data['type'] ?? null;
        if (is_string($type)) {
            $data['type'] = $this->resolveType($type);
        }

        $tier = $data['tier'] ?? null;
        if (is_string($tier)) {
            $data['tier'] = $this->resolveTier($tier);
        }

        if ($key !== null) {
            return data_get($data, $key, $default);
        }

        return $data;
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
                'description' => 'Center type.',
                'example' => 'branded',
            ],
            'name_translations' => [
                'description' => 'Localized center name keyed by locale code.',
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'string',
                ],
                'example' => [
                    'en' => 'Center Name',
                    'ar' => 'اسم المركز',
                ],
            ],
            'tier' => [
                'description' => 'Center tier.',
                'example' => 'premium',
            ],
            'is_featured' => [
                'description' => 'Whether the center is featured.',
                'example' => false,
            ],
            'branding_metadata' => [
                'description' => 'Branding metadata for branded centers.',
                'example' => ['primary_color' => '#000000'],
            ],
            'branding_metadata.primary_color' => [
                'description' => 'Primary branding color (required for branded centers).',
                'example' => '#000000',
            ],
            'admin' => [
                'description' => 'Initial admin user details.',
                'example' => [
                    'name' => 'Admin Name',
                    'email' => 'admin@example.com',
                ],
            ],
            'admin.name' => [
                'description' => 'Admin name.',
                'example' => 'Admin Name',
            ],
            'admin.email' => [
                'description' => 'Admin email address.',
                'example' => 'admin@example.com',
            ],
        ];
    }

    private function resolveType(string $type): int
    {
        return $type === 'branded' ? 1 : 0;
    }

    private function resolveTier(string $tier): int
    {
        return match ($tier) {
            'premium' => Center::TIER_PREMIUM,
            'vip' => Center::TIER_VIP,
            default => Center::TIER_STANDARD,
        };
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
