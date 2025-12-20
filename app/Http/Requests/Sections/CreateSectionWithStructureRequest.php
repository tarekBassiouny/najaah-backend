<?php

declare(strict_types=1);

namespace App\Http\Requests\Sections;

use Illuminate\Foundation\Http\FormRequest;

class CreateSectionWithStructureRequest extends FormRequest
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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
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
            'course_id' => [
                'description' => 'ID of the parent course.',
                'example' => 1,
            ],
            'title' => [
                'description' => 'Section title (base locale string).',
                'example' => 'Introduction',
            ],
            'description' => [
                'description' => 'Section description (base locale string).',
                'example' => 'Overview of the course.',
            ],
            'sort_order' => [
                'description' => 'Optional ordering index.',
                'example' => 1,
            ],
            'videos' => [
                'description' => 'Optional list of video IDs to attach to this section.',
                'example' => [5, 6],
            ],
            'videos.*' => [
                'description' => 'Video ID to attach.',
                'example' => 5,
            ],
            'pdfs' => [
                'description' => 'Optional list of PDF IDs to attach to this section.',
                'example' => [3, 4],
            ],
            'pdfs.*' => [
                'description' => 'PDF ID to attach.',
                'example' => 3,
            ],
        ];
    }
}
