<?php

declare(strict_types=1);

namespace App\Http\Requests\Playback;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthorizePlaybackRequest extends FormRequest
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
            'device_id' => ['required', 'string'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
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
