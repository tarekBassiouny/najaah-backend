<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Filters\Mobile\CategoryFilters;
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
        ];
    }

    public function filters(): CategoryFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $term = isset($data['search']) ? trim((string) $data['search']) : null;

        return new CategoryFilters(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            search: $term !== '' ? $term : null
        );
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
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
