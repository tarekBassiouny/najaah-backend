<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePdfRequest extends FormRequest
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
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
            'center_id' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_url' => ['prohibited'],
            'source_provider' => ['prohibited'],
            'source_type' => ['prohibited'],
            'file_extension' => ['prohibited'],
            'file_size_kb' => ['prohibited'],
            'created_by' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'PDF title translations object.',
                'example' => ['en' => 'Updated Notes', 'ar' => 'الملاحظات المحدثة'],
            ],
            'title_translations.en' => [
                'description' => 'PDF title in English.',
                'example' => 'Updated Notes',
            ],
            'title_translations.ar' => [
                'description' => 'PDF title in Arabic.',
                'example' => 'الملاحظات المحدثة',
            ],
            'description_translations' => [
                'description' => 'PDF description translations object.',
                'example' => ['en' => 'Updated description', 'ar' => 'الوصف المحدث'],
            ],
            'tags' => [
                'description' => 'Optional tags array.',
                'example' => ['notes', 'updated'],
            ],
        ];
    }
}
