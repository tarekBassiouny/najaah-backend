<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use App\Filters\Admin\StudentFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListStudentsRequest extends AdminListRequest
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
            'center_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
            'search' => ['sometimes', 'string'],
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
            'center_id' => [
                'description' => 'Filter students by center ID (super admin only).',
                'example' => '2',
            ],
            'status' => [
                'description' => 'Filter students by status (0 inactive, 1 active, 2 banned).',
                'example' => '1',
            ],
            'search' => [
                'description' => 'Search students by name, username, email, or phone.',
                'example' => '010',
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

    public function filters(): StudentFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new StudentFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            status: FilterInput::intOrNull($data, 'status'),
            search: FilterInput::stringOrNull($data, 'search')
        );
    }
}
