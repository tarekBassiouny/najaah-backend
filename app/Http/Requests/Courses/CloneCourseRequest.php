<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class CloneCourseRequest extends FormRequest
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
            'options' => ['nullable', 'array'],
            'options.include_sections' => ['sometimes', 'boolean'],
            'options.include_videos' => ['sometimes', 'boolean'],
            'options.include_pdfs' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $options = (array) ($this->input('options', []) ?? []);
        $defaults = [
            'include_sections' => true,
            'include_videos' => true,
            'include_pdfs' => true,
        ];

        $this->merge([
            'options' => array_merge($defaults, $options),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'options' => [
                'description' => 'Clone options.',
                'example' => [
                    'include_sections' => true,
                    'include_videos' => true,
                    'include_pdfs' => true,
                ],
            ],
            'options.include_sections' => [
                'description' => 'Whether to include sections in the clone.',
                'example' => true,
            ],
            'options.include_videos' => [
                'description' => 'Whether to include videos in the clone.',
                'example' => true,
            ],
            'options.include_pdfs' => [
                'description' => 'Whether to include PDFs in the clone.',
                'example' => true,
            ],
        ];
    }
}
