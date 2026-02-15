<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use App\Models\Center;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
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
        /** @var User|null $target */
        $target = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($target?->id)],
            'phone' => ['sometimes', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($target?->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeCenter = $this->route('center');
            $routeCenterId = null;

            if ($routeCenter instanceof Center) {
                $routeCenterId = (int) $routeCenter->id;
            } elseif (is_numeric($routeCenter)) {
                $routeCenterId = (int) $routeCenter;
            }

            if ($routeCenterId === null || ! $this->has('center_id')) {
                return;
            }

            $centerId = $this->input('center_id');
            if (! is_numeric($centerId) || (int) $centerId !== $routeCenterId) {
                $validator->errors()->add('center_id', 'Center ID must match the route center.');
            }
        });
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
                'example' => 'Updated Admin',
            ],
            'email' => [
                'description' => 'Admin email address.',
                'example' => 'updated.admin@example.com',
            ],
            'phone' => [
                'description' => 'Admin phone number.',
                'example' => '19990000004',
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
                'description' => 'Optional on system route. On center route, if provided, it must match route center.',
                'example' => 12,
            ],
        ];
    }
}
