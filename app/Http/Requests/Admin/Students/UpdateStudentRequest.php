<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof \App\Models\User ? $user->id : null;
        $centerId = $user instanceof \App\Models\User ? $user->center_id : null;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->where(function ($query) use ($centerId): void {
                        if (is_numeric($centerId)) {
                            $query->where('center_id', (int) $centerId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
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
            'status' => [
                'description' => 'Student status (0 inactive, 1 active, 2 banned).',
                'example' => 1,
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
