<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

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
            'name_translations' => ['sometimes', 'array', 'min:1'],
            'name_translations.*' => ['string', 'max:255'],
            'tier' => ['sometimes', 'string', 'in:standard,premium,vip'],
            'is_featured' => ['sometimes', 'boolean'],
            'branding_metadata' => ['sometimes', 'array'],
            'branding_metadata.primary_color' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        $tier = $data['tier'] ?? null;
        if (is_string($tier)) {
            $data['tier'] = $this->resolveTier($tier);
        }

        if ($key !== null) {
            return data_get($data, $key, $default);
        }

        return $data;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $centerId = $this->route('center');
            $center = is_numeric($centerId) ? \App\Models\Center::find((int) $centerId) : null;
            $type = (int) ($center?->type ?? 0);

            if ($type !== 1) {
                if ($this->has('branding_metadata')) {
                    $validator->errors()->add('branding_metadata', 'Branding metadata is only allowed for branded centers.');
                }

                return;
            }

            $branding = $this->input('branding_metadata');
            $brandingColor = is_array($branding) ? Arr::get($branding, 'primary_color') : null;

            if ($this->has('branding_metadata') && (! is_string($brandingColor) || $brandingColor === '')) {
                $validator->errors()->add('branding_metadata', 'Primary color is required for branded centers.');
            }
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name_translations' => [
                'description' => 'Localized center name.',
                'example' => ['en' => 'Updated Name'],
            ],
            'tier' => [
                'description' => 'Center tier identifier.',
                'example' => 'premium',
            ],
            'is_featured' => [
                'description' => 'Whether the center is featured.',
                'example' => true,
            ],
            'branding_metadata' => [
                'description' => 'Branding metadata for branded centers.',
                'example' => ['primary_color' => '#123456'],
            ],
            'branding_metadata.primary_color' => [
                'description' => 'Primary branding color.',
                'example' => '#123456',
            ],
        ];
    }

    private function resolveTier(string $tier): int
    {
        return match ($tier) {
            'premium' => \App\Models\Center::TIER_PREMIUM,
            'vip' => \App\Models\Center::TIER_VIP,
            default => \App\Models\Center::TIER_STANDARD,
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
