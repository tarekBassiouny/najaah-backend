<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreSystemSettingRequest extends FormRequest
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
            'key' => [
                'required',
                'string',
                'max:190',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('system_settings', 'key'),
            ],
            'value' => ['nullable', 'array'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'key' => [
                'description' => 'Unique system setting key.',
                'example' => 'student.default_country_code',
            ],
            'value' => [
                'description' => 'JSON object value for this setting.',
                'example' => ['code' => '+20'],
            ],
            'is_public' => [
                'description' => 'Whether this setting can be exposed publicly.',
                'example' => false,
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawIsPublic = $this->input('is_public');
        if (! is_string($rawIsPublic)) {
            return;
        }

        $normalized = filter_var($rawIsPublic, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return;
        }

        $this->merge([
            'is_public' => $normalized,
        ]);
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
