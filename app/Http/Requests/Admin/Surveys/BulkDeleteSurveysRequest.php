<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkDeleteSurveysRequest extends FormRequest
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
            'survey_ids' => ['required', 'array', 'min:1'],
            'survey_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'survey_ids' => [
                'description' => 'Survey IDs to delete.',
                'example' => [11, 12, 13],
            ],
            'survey_ids.*' => [
                'description' => 'Survey ID.',
                'example' => 11,
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
