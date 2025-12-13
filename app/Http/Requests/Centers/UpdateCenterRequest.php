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
