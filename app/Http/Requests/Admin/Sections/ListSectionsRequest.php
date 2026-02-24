<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use App\Filters\Admin\SectionFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListSectionsRequest extends AdminListRequest
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
            'search' => ['sometimes', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
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
                'description' => 'Search sections by translated title.',
                'example' => 'Intro',
            ],
            'is_published' => [
                'description' => 'Filter by section publish state.',
                'example' => '1',
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

    public function filters(): SectionFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new SectionFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            search: FilterInput::stringOrNull($data, 'search'),
            isPublished: FilterInput::boolOrNull($data, 'is_published'),
        );
    }
}
