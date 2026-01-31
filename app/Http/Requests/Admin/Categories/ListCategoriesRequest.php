<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Categories;

use App\Filters\Admin\CategoryFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListCategoriesRequest extends AdminListRequest
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
        return array_merge($this->listRules(), [
            'search' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['sometimes', 'integer', 'exists:categories,id'],
        ]);
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

    public function filters(): CategoryFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CategoryFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            search: FilterInput::stringOrNull($data, 'search'),
            isActive: FilterInput::boolOrNull($data, 'is_active'),
            parentId: FilterInput::intOrNull($data, 'parent_id')
        );
    }
}
