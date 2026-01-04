<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sort_order') && ! $this->has('order_index')) {
            $this->merge([
                'order_index' => $this->input('sort_order'),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255', 'not_regex:/^\\s*[\\[{]/'],
            'description' => ['sometimes', 'nullable', 'string', 'not_regex:/^\\s*[\\[{]/'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
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
                'description' => 'Section title (base locale string).',
                'example' => 'Updated Section Title',
            ],
            'description' => [
                'description' => 'Section description (base locale string).',
                'example' => 'Updated description.',
            ],
            'order_index' => [
                'description' => 'Optional ordering index.',
                'example' => 2,
            ],
        ];
    }
}
