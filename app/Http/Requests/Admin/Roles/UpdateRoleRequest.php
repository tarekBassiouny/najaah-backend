<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Roles;

use App\Models\Center;
use App\Models\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        /** @var Role|null $role */
        $role = $this->route('role');
        $roleId = $role?->id;
        $centerId = $this->resolvedCenterId($role);

        return [
            'name_translations' => ['sometimes', 'array', 'min:1'],
            'name_translations.en' => ['nullable', 'string', 'max:100'],
            'name_translations.ar' => ['nullable', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('roles', 'slug')
                    ->ignore($roleId)
                    ->where(function ($query) use ($centerId): void {
                        if ($centerId === null) {
                            $query->whereNull('center_id');

                            return;
                        }

                        $query->where('center_id', $centerId);
                    }),
                Rule::notIn($this->reservedSlugsForScope($centerId)),
            ],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.en' => ['nullable', 'string', 'max:255'],
            'description_translations.ar' => ['nullable', 'string', 'max:255'],
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
            'name_translations' => [
                'description' => 'Role name translations object.',
                'example' => ['en' => 'Support Admin', 'ar' => 'مدير الدعم'],
            ],
            'name_translations.en' => [
                'description' => 'Role name in English.',
                'example' => 'Support Admin',
            ],
            'name_translations.ar' => [
                'description' => 'Role name in Arabic.',
                'example' => 'مدير الدعم',
            ],
            'slug' => [
                'description' => 'Unique role identifier.',
                'example' => 'support_admin',
            ],
            'description_translations' => [
                'description' => 'Role description translations object.',
                'example' => ['en' => 'Handles support workflows.', 'ar' => 'يدير سير عمل الدعم.'],
            ],
            'description_translations.en' => [
                'description' => 'Role description in English.',
                'example' => 'Handles support workflows.',
            ],
            'description_translations.ar' => [
                'description' => 'Role description in Arabic.',
                'example' => 'يدير سير عمل الدعم.',
            ],
        ];
    }

    private function resolvedCenterId(?Role $role): ?int
    {
        $routeCenter = $this->route('center');

        if ($routeCenter instanceof Center) {
            return (int) $routeCenter->id;
        }

        if (is_numeric($routeCenter)) {
            return (int) $routeCenter;
        }

        return is_numeric($role?->center_id) ? (int) $role->center_id : null;
    }

    /**
     * @return array<int, string>
     */
    private function reservedSlugsForScope(?int $centerId): array
    {
        if ($centerId === null) {
            return ['student'];
        }

        return [
            'super_admin',
            'center_owner',
            'student',
        ];
    }
}
