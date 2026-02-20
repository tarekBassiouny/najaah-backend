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
        $resolvedCenterId = $this->resolveCenterId($target);

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'email',
                'max:190',
                Rule::unique('users', 'email')
                    ->ignore($target?->id)
                    ->where(function ($query): void {
                        $query->where('is_student', false)
                            ->whereNull('deleted_at');
                    }),
            ],
            'phone' => [
                'sometimes',
                'string',
                'regex:/^[1-9][0-9]{9}$/',
                Rule::unique('users', 'phone')
                    ->ignore($target?->id)
                    ->where(function ($query) use ($resolvedCenterId): void {
                        $query->where('is_student', false)
                            ->whereNull('deleted_at');

                        if ($resolvedCenterId !== null) {
                            $query->where('center_id', $resolvedCenterId);
                        } else {
                            $query->whereNull('center_id');
                        }
                    }),
            ],
            'country_code' => ['sometimes', 'string', 'max:8', 'regex:/^(\+\d{1,6}|00\d{1,6})$/'],
            'password' => ['prohibited'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
        ];
    }

    private function resolveCenterId(?User $target): ?int
    {
        $routeCenter = $this->route('center');
        if ($routeCenter instanceof Center) {
            return (int) $routeCenter->id;
        }

        if (is_numeric($routeCenter)) {
            return (int) $routeCenter;
        }

        $centerId = $this->input('center_id');
        if (is_numeric($centerId)) {
            return (int) $centerId;
        }

        if ($target instanceof User && is_numeric($target->center_id)) {
            return (int) $target->center_id;
        }

        return null;
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

        if ($updates !== []) {
            $this->merge($updates);
        }
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
            'country_code' => [
                'description' => 'Admin country dialing code with + or 00 prefix.',
                'example' => '+1',
            ],
            'password' => [
                'description' => 'Not accepted. Use password reset/invite flow endpoints.',
                'example' => null,
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
