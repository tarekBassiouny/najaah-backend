<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use Illuminate\Foundation\Http\FormRequest;

class FinalizePdfUploadSessionRequest extends FormRequest
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
            'pdf_id' => ['sometimes', 'integer', 'exists:pdfs,id'],
            'title_translations' => ['required_without:pdf_id', 'array', 'min:1'],
            'title_translations.en' => ['nullable', 'string', 'max:255'],
            'title_translations.ar' => ['nullable', 'string', 'max:255'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'error_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'pdf_id' => [
                'description' => 'Existing PDF ID to link with the upload session.',
                'example' => 12,
            ],
            'title_translations' => [
                'description' => 'PDF title translations object (required without pdf_id).',
                'example' => ['en' => 'Lesson Notes', 'ar' => 'ملاحظات الدرس'],
            ],
            'title_translations.en' => [
                'description' => 'PDF title in English.',
                'example' => 'Lesson Notes',
            ],
            'title_translations.ar' => [
                'description' => 'PDF title in Arabic.',
                'example' => 'ملاحظات الدرس',
            ],
            'description_translations' => [
                'description' => 'PDF description translations object.',
                'example' => ['en' => 'Downloadable notes.', 'ar' => 'ملاحظات قابلة للتنزيل.'],
            ],
            'error_message' => [
                'description' => 'Optional error message to record if finalize fails.',
                'example' => 'Upload failed',
            ],
        ];
    }
}
