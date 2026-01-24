<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Categories;

use Illuminate\Foundation\Http\FormRequest;

class ListCategoriesRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'search' => [
                'description' => 'Search by category title.',
                'example' => 'Science',
            ],
            'is_active' => [
                'description' => 'Filter by active status.',
                'example' => 'true',
            ],
            'parent_id' => [
                'description' => 'Filter by parent category id.',
                'example' => '10',
            ],
        ];
    }
}
