<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Roles;

use App\Models\Center;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
        $centerId = $this->resolvedCenterId();

        return [
            'name_translations' => ['required', 'array', 'min:1'],
            'name_translations.en' => ['required', 'string', 'max:100'],
            'name_translations.ar' => ['nullable', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'slug')
                    ->where(function ($query) use ($centerId): void {
                        if ($centerId === null) {
                            $query->whereNull('center_id');

                            return;
                        }

                        $query->where('center_id', $centerId);
                    }),
                Rule::notIn($this->reservedSlugsForScope($centerId)),
            ],
            'description_translations' => ['nullable', 'array'],
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
                'example' => ['en' => 'Content Admin', 'ar' => 'مدير المحتوى'],
            ],
            'name_translations.en' => [
                'description' => 'Role name in English (required).',
                'example' => 'Content Admin',
            ],
            'name_translations.ar' => [
                'description' => 'Role name in Arabic (optional).',
                'example' => 'مدير المحتوى',
            ],
            'slug' => [
                'description' => 'Unique role identifier.',
                'example' => 'content_admin',
            ],
            'description_translations' => [
                'description' => 'Role description translations object.',
                'example' => ['en' => 'Manages course and video content.', 'ar' => 'يدير محتوى الدورات والفيديو.'],
            ],
            'description_translations.en' => [
                'description' => 'Role description in English.',
                'example' => 'Manages course and video content.',
            ],
            'description_translations.ar' => [
                'description' => 'Role description in Arabic.',
                'example' => 'يدير محتوى الدورات والفيديو.',
            ],
        ];
    }

    private function resolvedCenterId(): ?int
    {
        $routeCenter = $this->route('center');

        if ($routeCenter instanceof Center) {
            return (int) $routeCenter->id;
        }

        return is_numeric($routeCenter) ? (int) $routeCenter : null;
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
