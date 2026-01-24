<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
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
            'encoding_status' => ['prohibited'],
            'lifecycle_status' => ['prohibited'],
            'upload_session_id' => ['prohibited'],
            'upload_session' => ['prohibited'],
            'source_id' => ['prohibited'],
            'source_url' => ['prohibited'],
            'source_type' => ['prohibited'],
            'source_provider' => ['prohibited'],
            'library_id' => ['prohibited'],
            'original_filename' => ['prohibited'],
            'duration_seconds' => ['prohibited'],
            'thumbnail_url' => ['prohibited'],
            'thumbnail_urls' => ['prohibited'],
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
                'example' => ['en' => 'Updated title', 'ar' => 'العنوان المحدث'],
            ],
            'title_translations.en' => [
                'description' => 'Video title in English.',
                'example' => 'Updated title',
            ],
            'title_translations.ar' => [
                'description' => 'Video title in Arabic.',
                'example' => 'العنوان المحدث',
            ],
            'description_translations' => [
                'description' => 'Video description translations object.',
                'example' => ['en' => 'Updated description', 'ar' => 'الوصف المحدث'],
            ],
            'tags' => [
                'description' => 'Optional tags array.',
                'example' => ['topic' => 'intro'],
            ],
        ];
    }
}
