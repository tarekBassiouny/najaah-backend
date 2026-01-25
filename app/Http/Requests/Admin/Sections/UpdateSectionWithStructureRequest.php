<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionWithStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title_translations' => ['sometimes', 'array', 'min:1'],
            'title_translations.en' => ['nullable', 'string', 'max:255'],
            'title_translations.ar' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['integer', 'exists:videos,id'],
            'pdfs' => ['nullable', 'array'],
            'pdfs.*' => ['integer', 'exists:pdfs,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Section title translations object.',
                'example' => ['en' => 'Updated Section', 'ar' => 'القسم المحدث'],
            ],
            'title_translations.en' => [
                'description' => 'Section title in English.',
                'example' => 'Updated Section',
            ],
            'title_translations.ar' => [
                'description' => 'Section title in Arabic.',
                'example' => 'القسم المحدث',
            ],
            'description_translations' => [
                'description' => 'Section description translations object.',
                'example' => ['en' => 'Updated description.', 'ar' => 'الوصف المحدث.'],
            ],
            'sort_order' => [
                'description' => 'Optional ordering index.',
                'example' => 2,
            ],
            'videos' => [
                'description' => 'Optional list of video IDs to attach to this section.',
                'example' => [5, 6],
            ],
            'pdfs' => [
                'description' => 'Optional list of PDF IDs to attach to this section.',
                'example' => [3, 4],
            ],
        ];
    }
}
