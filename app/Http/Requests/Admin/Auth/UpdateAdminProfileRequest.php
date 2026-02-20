<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAdminProfileRequest extends FormRequest
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
        $user = $this->user('admin');
        $userId = $user?->id;
        $centerId = is_numeric($user?->center_id) ? (int) $user->center_id : null;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => [
                'sometimes',
                'string',
                'regex:/^[1-9][0-9]{9}$/',
                Rule::unique('users', 'phone')
                    ->ignore($userId)
                    ->where(function ($query) use ($centerId): void {
                        $query->where('is_student', false)
                            ->whereNull('deleted_at');

                        if ($centerId !== null) {
                            $query->where('center_id', $centerId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'country_code' => ['sometimes', 'string', 'max:8', 'regex:/^(\+\d{1,6}|00\d{1,6})$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        if ($this->has('phone')) {
            $sanitizedPhone = preg_replace('/\D+/', '', (string) $this->input('phone'));
            $updates['phone'] = trim((string) $sanitizedPhone);
        }

        if ($this->has('country_code')) {
            $sanitizedCountry = preg_replace('/[^\d+]+/', '', (string) $this->input('country_code'));
            $updates['country_code'] = trim((string) $sanitizedCountry);
        }

        if (! empty($updates)) {
            $this->merge($updates);
        }
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
                'description' => 'Admin display name.',
                'example' => 'Tarek Admin',
            ],
            'phone' => [
                'description' => 'Admin phone number (10 digits, no country code).',
                'example' => '19990000003',
            ],
            'country_code' => [
                'description' => 'Admin country dialing code with + or 00 prefix.',
                'example' => '+1',
            ],
        ];
    }
}
