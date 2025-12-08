<?php

declare(strict_types=1);

namespace App\Http\Requests\Sections;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
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
            'sort_order' => [
                'description' => 'Optional ordering index.',
                'example' => 2,
            ],
        ];
    }
}
