<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkAssignAdminUsersToCentersRequest extends FormRequest
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
            'assignments.*.user_id' => ['required', 'integer', 'distinct'],
            'assignments.*.center_id' => ['required', 'integer', 'exists:centers,id'],
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

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'assignments' => [
                'description' => 'List of admin-center assignments to apply.',
                'example' => [
                    ['user_id' => 101, 'center_id' => 12],
                    ['user_id' => 102, 'center_id' => 15],
                ],
            ],
            'assignments.*.user_id' => [
                'description' => 'Admin user ID.',
                'example' => 101,
            ],
            'assignments.*.center_id' => [
                'description' => 'Center ID to assign this admin to.',
                'example' => 12,
            ],
        ];
    }
}
