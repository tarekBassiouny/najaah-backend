<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\SurveyAssignableType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AssignSurveyRequest extends FormRequest
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
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.type' => ['required', 'string', Rule::in(array_column(SurveyAssignableType::cases(), 'value'))],
            'assignments.*.id' => ['nullable', 'integer', 'min:1', 'required_unless:assignments.*.type,all'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'assignments' => [
                'description' => 'Array of assignment targets',
                'example' => [
                    ['type' => 'all'],
                    ['type' => 'course', 'id' => 1],
                    ['type' => 'user', 'id' => 42],
                ],
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
