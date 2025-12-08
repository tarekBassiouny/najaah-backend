<?php

declare(strict_types=1);

namespace App\Http\Requests\Sections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionWithStructureRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['integer', 'exists:videos,id'],
            'pdfs' => ['nullable', 'array'],
            'pdfs.*' => ['integer', 'exists:pdfs,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'Section title (base locale string).',
                'example' => 'Updated Section',
            ],
            'description' => [
                'description' => 'Section description (base locale string).',
                'example' => 'Updated description.',
            ],
            'sort_order' => [
                'description' => 'Optional ordering index.',
                'example' => 2,
            ],
            'videos' => [
                'description' => 'Optional list of video IDs to attach to this section.',
                'example' => [5, 6],
            ],
            'pdfs' => [
                'description' => 'Optional list of PDF IDs to attach to this section.',
                'example' => [3, 4],
            ],
        ];
    }
}
