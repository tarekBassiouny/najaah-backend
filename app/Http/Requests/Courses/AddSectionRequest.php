<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class AddSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && ! $this->has('title_translations')) {
            $this->merge([
                'title_translations' => ['en' => ($this->input('title', ''))],
            ]);
        }

        if ($this->has('description') && ! $this->has('description_translations')) {
            $this->merge([
                'description_translations' => ['en' => ($this->input('description', ''))],
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'title_translations' => ['sometimes', 'array'],
            'description_translations' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'title' => [
                'description' => 'Section title (defaults to en translation if translations not provided).',
                'example' => 'Introduction',
            ],
            'description' => [
                'description' => 'Optional section description.',
                'example' => 'Overview of the course.',
            ],
            'order_index' => [
                'description' => 'Optional order within the course.',
                'example' => '1',
            ],
        ];
    }
}
