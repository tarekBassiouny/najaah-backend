<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Education;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
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
            'name_translations' => ['required', 'array', 'min:1'],
            'name_translations.en' => ['required', 'string', 'max:100'],
            'name_translations.ar' => ['nullable', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100'],
            'type' => ['required', 'integer', 'in:0,1,2,3'],
            'address' => ['nullable', 'string', 'max:1000'],
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
                'description' => 'Localized school name map.',
                'example' => ['en' => 'International School', 'ar' => 'مدرسة دولية'],
            ],
            'slug' => [
                'description' => 'Optional slug. Auto-generated if omitted.',
                'example' => 'international-school',
            ],
            'type' => [
                'description' => 'School type enum (0..3).',
                'example' => 2,
            ],
            'address' => [
                'description' => 'Optional school address.',
                'example' => 'Nasr City, Cairo',
            ],
            'is_active' => [
                'description' => 'Whether school is active.',
                'example' => true,
            ],
        ];
    }
}
