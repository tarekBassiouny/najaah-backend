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
