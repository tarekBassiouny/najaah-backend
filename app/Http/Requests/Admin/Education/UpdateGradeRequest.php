<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Education;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
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
            'name_translations' => ['sometimes', 'array', 'min:1'],
            'name_translations.en' => ['sometimes', 'required', 'string', 'max:100'],
            'name_translations.ar' => ['nullable', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100'],
            'stage' => ['sometimes', 'integer', 'in:0,1,2,3,4'],
            'order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'name_translations' => [
                'description' => 'Localized grade name map.',
                'example' => ['en' => 'Grade 10', 'ar' => 'الصف العاشر'],
            ],
            'slug' => [
                'description' => 'Optional new slug.',
                'example' => 'grade-10',
            ],
            'stage' => [
                'description' => 'Educational stage enum (0..4).',
                'example' => 2,
            ],
            'order' => [
                'description' => 'Display order within grades list.',
                'example' => 11,
            ],
            'is_active' => [
                'description' => 'Whether grade is active.',
                'example' => false,
            ],
        ];
    }
}
