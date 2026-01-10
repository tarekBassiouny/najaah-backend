<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use Illuminate\Foundation\Http\FormRequest;

class StorePdfUploadSessionRequest extends FormRequest
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
            'original_filename' => ['required', 'string', 'max:255'],
            'file_size_kb' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'original_filename' => [
                'description' => 'Original filename for the PDF upload.',
                'example' => 'notes.pdf',
            ],
            'file_size_kb' => [
                'description' => 'Optional file size in KB.',
                'example' => 2048,
            ],
        ];
    }
}
