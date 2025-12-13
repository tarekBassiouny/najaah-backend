<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrollments;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            $this->merge(['status' => 'ACTIVE']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_student', true),
            ],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'DEACTIVATED', 'CANCELLED'])],
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
