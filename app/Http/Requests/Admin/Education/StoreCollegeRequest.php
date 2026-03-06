<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Education;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeRequest extends FormRequest
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
            'type' => ['sometimes', 'nullable', 'integer', 'between:0,255'],
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
                'description' => 'Localized college name map.',
                'example' => ['en' => 'Faculty of Engineering', 'ar' => 'كلية الهندسة'],
            ],
            'slug' => [
                'description' => 'Optional slug. Auto-generated if omitted.',
                'example' => 'faculty-of-engineering',
            ],
            'type' => [
                'description' => 'Optional college type value (0..255).',
                'example' => 1,
            ],
            'address' => [
                'description' => 'Optional college address.',
                'example' => 'Giza, Egypt',
            ],
            'is_active' => [
                'description' => 'Whether college is active.',
                'example' => true,
            ],
        ];
    }
}
