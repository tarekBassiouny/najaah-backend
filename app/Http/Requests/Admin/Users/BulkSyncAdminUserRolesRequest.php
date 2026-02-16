<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkSyncAdminUserRolesRequest extends FormRequest
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
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
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
            'user_ids' => [
                'description' => 'Admin user IDs to update roles for.',
                'example' => [11, 12, 13],
            ],
            'user_ids.*' => [
                'description' => 'Admin user ID.',
                'example' => 11,
            ],
            'role_ids' => [
                'description' => 'Role IDs to assign to each admin user.',
                'example' => [1, 2],
            ],
            'role_ids.*' => [
                'description' => 'Role ID.',
                'example' => 1,
            ],
        ];
    }
}
