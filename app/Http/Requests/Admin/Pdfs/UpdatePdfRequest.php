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
            'title' => ['sometimes', 'required', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['sometimes', 'nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'title_translations' => ['prohibited'],
            'description_translations' => ['prohibited'],
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
            'title' => [
                'description' => 'Updated PDF title.',
                'example' => 'Updated Notes',
            ],
            'description' => [
                'description' => 'Updated description.',
                'example' => 'Updated description',
            ],
        ];
    }
}
