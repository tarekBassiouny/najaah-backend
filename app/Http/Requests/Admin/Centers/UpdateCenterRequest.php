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
            'tier' => ['sometimes', 'integer'],
            'is_featured' => ['sometimes', 'boolean'],
            'branding_metadata' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var \App\Models\Center|null $center */
            $center = $this->route('center');
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
                'example' => 1,
            ],
            'is_featured' => [
                'description' => 'Whether the center is featured.',
                'example' => true,
            ],
            'branding_metadata' => [
                'description' => 'Branding metadata for branded centers.',
                'example' => ['primary_color' => '#123456'],
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
