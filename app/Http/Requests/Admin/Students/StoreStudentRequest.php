<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use App\Models\User;
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
        $resolvedCenterId = $this->resolveCenterId();

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->where(function ($query) use ($resolvedCenterId): void {
                        $query->where('is_student', true)
                            ->whereNull('deleted_at');

                        if ($resolvedCenterId !== null) {
                            $query->where('center_id', $resolvedCenterId);
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
                    ->where(function ($query) use ($resolvedCenterId): void {
                        $query->where('is_student', true)
                            ->whereNull('deleted_at');

                        if ($resolvedCenterId !== null) {
                            $query->where('center_id', $resolvedCenterId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'country_code' => ['required', 'string', 'max:8'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
        ];
    }

    private function resolveCenterId(): ?int
    {
        $centerId = $this->input('center_id');

        if (is_numeric($centerId)) {
            return (int) $centerId;
        }

        $user = $this->user();

        if ($user instanceof User && ! $user->hasRole('super_admin') && is_numeric($user->center_id)) {
            return (int) $user->center_id;
        }

        return null;
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
