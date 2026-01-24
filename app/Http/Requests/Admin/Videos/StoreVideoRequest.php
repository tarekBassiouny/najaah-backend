<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
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
            'title_translations' => ['required', 'array', 'min:1'],
            'title_translations.en' => ['required', 'string', 'max:255'],
            'title_translations.ar' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Video title translations object.',
                'example' => ['en' => 'Introduction', 'ar' => 'مقدمة'],
            ],
            'title_translations.en' => [
                'description' => 'Video title in English (required).',
                'example' => 'Introduction',
            ],
            'title_translations.ar' => [
                'description' => 'Video title in Arabic (optional).',
                'example' => 'مقدمة',
            ],
            'description_translations' => [
                'description' => 'Video description translations object.',
                'example' => ['en' => 'Overview of the lesson.', 'ar' => 'نظرة عامة على الدرس.'],
            ],
            'tags' => [
                'description' => 'Optional tags array.',
                'example' => ['module' => 'intro'],
            ],
        ];
    }
}
