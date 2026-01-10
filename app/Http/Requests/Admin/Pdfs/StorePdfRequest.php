<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use Illuminate\Foundation\Http\FormRequest;

class StorePdfRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['sometimes', 'nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'title_translations' => ['prohibited'],
            'description_translations' => ['prohibited'],
            'upload_session_id' => ['sometimes', 'integer', 'exists:pdf_upload_sessions,id'],
            'source_id' => ['required_without:upload_session_id', 'string', 'max:2048'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'file_extension' => ['required_without:upload_session_id', 'string', 'max:10'],
            'file_size_kb' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'PDF title in the request locale.',
                'example' => 'Lesson Notes',
            ],
            'description' => [
                'description' => 'Optional description in the request locale.',
                'example' => 'Downloadable notes.',
            ],
            'upload_session_id' => [
                'description' => 'Upload session ID used to finalize the PDF.',
                'example' => 12,
            ],
            'source_id' => [
                'description' => 'Object key for a finalized upload when no session is provided.',
                'example' => 'centers/1/pdfs/demo.pdf',
            ],
            'source_url' => [
                'description' => 'Optional public URL if externally hosted.',
                'example' => 'https://cdn.example.com/demo.pdf',
            ],
            'file_extension' => [
                'description' => 'File extension when no upload session is provided.',
                'example' => 'pdf',
            ],
            'file_size_kb' => [
                'description' => 'Optional file size in KB.',
                'example' => 1024,
            ],
        ];
    }
}
