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
            'title' => ['required_without:pdf_id', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['sometimes', 'nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'title_translations' => ['prohibited'],
            'description_translations' => ['prohibited'],
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
            'title' => [
                'description' => 'PDF title when creating a new record.',
                'example' => 'Lesson Notes',
            ],
            'description' => [
                'description' => 'Optional description when creating a new record.',
                'example' => 'Downloadable notes.',
            ],
            'error_message' => [
                'description' => 'Optional error message to record if finalize fails.',
                'example' => 'Upload failed',
            ],
        ];
    }
}
