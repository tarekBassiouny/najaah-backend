<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStylingSectionRequest extends FormRequest
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
            'primary_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,8}$/'],
            'font_family' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_color.regex' => 'The primary color must be a valid hex color code.',
            'secondary_color.regex' => 'The secondary color must be a valid hex color code.',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'primary_color' => [
                'description' => 'Primary brand color in hex format.',
                'example' => '#3B82F6',
            ],
            'secondary_color' => [
                'description' => 'Secondary brand color in hex format.',
                'example' => '#1E40AF',
            ],
            'font_family' => [
                'description' => 'Font family name.',
                'example' => 'Inter',
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
