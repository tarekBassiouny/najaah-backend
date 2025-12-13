<?php

declare(strict_types=1);

namespace App\Http\Requests\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class StorePdfRequest extends FormRequest
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
            'title_translations' => ['required', 'array'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:51200'],
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'section_id' => ['sometimes', 'integer', 'exists:sections,id'],
            'video_id' => ['sometimes', 'integer', 'exists:videos,id'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Localized title for the PDF.',
                'example' => ['en' => 'Lesson Notes', 'ar' => 'ملاحظات الدرس'],
            ],
            'description_translations' => [
                'description' => 'Optional localized description.',
                'example' => ['en' => 'Downloadable PDF for lesson 1'],
            ],
            'file' => [
                'description' => 'PDF file to upload (max 50MB).',
            ],
            'course_id' => [
                'description' => 'Optional course to attach the PDF to.',
                'example' => 1,
            ],
            'section_id' => [
                'description' => 'Optional section to attach the PDF to (must belong to the course).',
                'example' => 2,
            ],
            'video_id' => [
                'description' => 'Optional video association (must belong to the course).',
                'example' => 3,
            ],
        ];
    }
}
