<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
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
            'name' => [
                'description' => 'Admin name.',
                'example' => 'Jane Admin',
            ],
            'email' => [
                'description' => 'Admin email address.',
                'example' => 'jane.admin@example.com',
            ],
            'phone' => [
                'description' => 'Admin phone number.',
                'example' => '19990000003',
            ],
            'password' => [
                'description' => 'Admin password.',
                'example' => 'secret123',
            ],
            'status' => [
                'description' => 'Admin status (0 inactive, 1 active, 2 banned).',
                'example' => 1,
            ],
            'center_id' => [
                'description' => 'Optional center assignment for admin.',
                'example' => 12,
            ],
        ];
    }
}
