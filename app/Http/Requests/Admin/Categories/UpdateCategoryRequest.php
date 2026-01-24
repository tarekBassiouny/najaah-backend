<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Categories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
            'title_translations' => ['sometimes', 'array'],
            'description_translations' => ['sometimes', 'nullable', 'array'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'title_translations' => [
                'description' => 'Localized category title keyed by locale.',
                'example' => ['en' => 'Science', 'ar' => 'العلوم'],
            ],
            'description_translations' => [
                'description' => 'Localized category description keyed by locale.',
                'example' => ['en' => 'STEM courses', 'ar' => 'دورات العلوم'],
            ],
            'parent_id' => [
                'description' => 'Optional parent category id.',
                'example' => 2,
            ],
            'order_index' => [
                'description' => 'Optional ordering index (lower shows first).',
                'example' => 1,
            ],
            'is_active' => [
                'description' => 'Whether the category is active.',
                'example' => true,
            ],
        ];
    }
}
