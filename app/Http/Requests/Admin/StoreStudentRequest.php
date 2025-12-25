<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->where(function ($query): void {
                        $centerId = $this->input('center_id');
                        if (is_numeric($centerId)) {
                            $query->where('center_id', (int) $centerId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'phone')
                    ->where(function ($query): void {
                        $centerId = $this->input('center_id');
                        if (is_numeric($centerId)) {
                            $query->where('center_id', (int) $centerId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'country_code' => ['required', 'string', 'max:8'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Student name.',
                'example' => 'Student One',
            ],
            'email' => [
                'description' => 'Student email address.',
                'example' => 'student@example.com',
            ],
            'phone' => [
                'description' => 'Student phone number.',
                'example' => '19990000001',
            ],
            'country_code' => [
                'description' => 'Student country code.',
                'example' => '+20',
            ],
            'center_id' => [
                'description' => 'Optional center assignment for student.',
                'example' => 12,
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
