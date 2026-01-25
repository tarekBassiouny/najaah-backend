<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sort_order') && ! $this->has('order_index')) {
            $this->merge([
                'order_index' => $this->input('sort_order'),
            ]);
        }
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
            'order_index' => ['sometimes', 'integer', 'min:0'],
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
                'example' => ['en' => 'Updated Section Title', 'ar' => 'عنوان القسم المحدث'],
            ],
            'title_translations.en' => [
                'description' => 'Section title in English.',
                'example' => 'Updated Section Title',
            ],
            'title_translations.ar' => [
                'description' => 'Section title in Arabic.',
                'example' => 'عنوان القسم المحدث',
            ],
            'description_translations' => [
                'description' => 'Section description translations object.',
                'example' => ['en' => 'Updated description.', 'ar' => 'الوصف المحدث.'],
            ],
            'order_index' => [
                'description' => 'Optional ordering index.',
                'example' => 2,
            ],
        ];
    }
}
