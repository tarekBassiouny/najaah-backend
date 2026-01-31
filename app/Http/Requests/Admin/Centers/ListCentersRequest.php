<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Filters\Admin\CenterFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListCentersRequest extends AdminListRequest
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
            'slug' => ['sometimes', 'string'],
            'type' => ['sometimes', 'integer'],
            'tier' => ['sometimes', 'integer'],
            'is_featured' => ['sometimes', 'boolean'],
            'onboarding_status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
        ]);
    }

    public function filters(): CenterFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CenterFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            slug: FilterInput::stringOrNull($data, 'slug'),
            type: FilterInput::intOrNull($data, 'type'),
            tier: FilterInput::intOrNull($data, 'tier'),
            isFeatured: FilterInput::boolOrNull($data, 'is_featured'),
            onboardingStatus: FilterInput::stringOrNull($data, 'onboarding_status'),
            search: FilterInput::stringOrNull($data, 'search'),
            createdFrom: FilterInput::stringOrNull($data, 'created_from'),
            createdTo: FilterInput::stringOrNull($data, 'created_to')
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
            'slug' => [
                'description' => 'Filter centers by slug.',
                'example' => 'center-1',
            ],
            'type' => [
                'description' => 'Filter centers by type.',
                'example' => '1',
            ],
            'tier' => [
                'description' => 'Filter centers by tier.',
                'example' => '1',
            ],
            'is_featured' => [
                'description' => 'Filter centers by featured flag.',
                'example' => '1',
            ],
            'onboarding_status' => [
                'description' => 'Filter centers by onboarding status.',
                'example' => 'ACTIVE',
            ],
            'search' => [
                'description' => 'Search centers by name.',
                'example' => 'Academy',
            ],
            'created_from' => [
                'description' => 'Filter centers created from the given date.',
                'example' => '2024-01-01',
            ],
            'created_to' => [
                'description' => 'Filter centers created up to the given date.',
                'example' => '2024-12-31',
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
