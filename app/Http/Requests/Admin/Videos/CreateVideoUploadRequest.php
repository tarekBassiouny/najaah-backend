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
}
