<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use Illuminate\Foundation\Http\FormRequest;

class AddSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void {}

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'title_translations' => ['prohibited'],
            'description_translations' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
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
