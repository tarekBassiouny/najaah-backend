<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class CreateVideoUploadRequest extends FormRequest
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
            'original_filename' => ['required', 'string', 'max:255'],
            'file_size_bytes' => ['required', 'integer', 'min:1', 'max:2147483648'],
            'mime_type' => ['required', 'string', 'in:video/mp4,video/quicktime,video/x-matroska,video/webm,video/x-msvideo,video/mpeg,video/3gpp'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Localized video title map.',
                'example' => ['en' => 'Lesson 1', 'ar' => 'الدرس الأول'],
            ],
            'description_translations' => [
                'description' => 'Optional localized video description map.',
                'example' => ['en' => 'Introduction', 'ar' => 'مقدمة'],
            ],
            'tags' => [
                'description' => 'Optional list of video tags.',
                'example' => ['biology', 'grade-10'],
            ],
            'original_filename' => [
                'description' => 'Source file name uploaded by admin.',
                'example' => 'lesson-1.mp4',
            ],
            'file_size_bytes' => [
                'description' => 'File size in bytes.',
                'example' => 104857600,
            ],
            'mime_type' => [
                'description' => 'Detected MIME type of uploaded file.',
                'example' => 'video/mp4',
            ],
        ];
    }
}
