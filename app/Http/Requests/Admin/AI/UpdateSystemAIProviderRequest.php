<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AI;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSystemAIProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'max:100'],
            'is_enabled' => ['sometimes', 'boolean'],
            'default_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'models' => ['sometimes', 'array', 'min:1'],
            'models.*' => ['required_with:models', 'string', 'max:120'],
            'api_key' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'reset_api_key' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'display_name' => [
                'description' => 'Optional provider label shown in admin UI.',
                'example' => 'OpenAI',
            ],
            'is_enabled' => [
                'description' => 'Enable or disable this provider globally.',
                'example' => true,
            ],
            'default_model' => [
                'description' => 'Default model for this provider.',
                'example' => 'gpt-4o-mini',
            ],
            'models' => [
                'description' => 'Allowed model IDs for this provider.',
                'example' => ['gpt-4o-mini', 'gpt-4.1-mini'],
            ],
            'api_key' => [
                'description' => 'Optional API key override. Never returned by API.',
                'example' => 'sk-xxxxxxxx',
            ],
            'reset_api_key' => [
                'description' => 'Set true to remove stored API key and fallback to env key.',
                'example' => false,
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_enabled', 'reset_api_key'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized === null) {
                continue;
            }

            $this->merge([$field => $normalized]);
        }
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
