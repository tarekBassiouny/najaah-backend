<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Filters\Admin\CenterFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Models\Center;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

class ListCentersRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'slug' => ['sometimes', 'string'],
            'type' => ['sometimes', 'integer', Rule::in([0, 1])],
            'tier' => ['sometimes', 'integer', Rule::in([0, 1, 2])],
            'is_featured' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'integer', Rule::in([0, 1])],
            'is_demo' => ['sometimes', 'boolean'],
            'onboarding_status' => ['sometimes', 'string', Rule::in([
                Center::ONBOARDING_DRAFT,
                Center::ONBOARDING_IN_PROGRESS,
                Center::ONBOARDING_FAILED,
                Center::ONBOARDING_ACTIVE,
            ])],
            'search' => ['sometimes', 'string'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
            'updated_from' => ['sometimes', 'date'],
            'updated_to' => ['sometimes', 'date'],
            'deleted' => ['sometimes', 'string', Rule::in(['active', 'with_deleted', 'only_deleted'])],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['is_featured', 'is_demo'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $normalized = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                $data[$field] = $normalized;
            }
        }

        $this->replace($data);
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
            status: FilterInput::intOrNull($data, 'status'),
            isDemo: FilterInput::boolOrNull($data, 'is_demo'),
            onboardingStatus: FilterInput::stringOrNull($data, 'onboarding_status'),
            search: FilterInput::stringOrNull($data, 'search'),
            createdFrom: FilterInput::stringOrNull($data, 'created_from'),
            createdTo: FilterInput::stringOrNull($data, 'created_to'),
            updatedFrom: FilterInput::stringOrNull($data, 'updated_from'),
            updatedTo: FilterInput::stringOrNull($data, 'updated_to'),
            deleted: FilterInput::stringOrNull($data, 'deleted')
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
            'status' => [
                'description' => 'Filter centers by status (1 active, 0 inactive).',
                'example' => '1',
            ],
            'is_demo' => [
                'description' => 'Filter demo centers.',
                'example' => '0',
            ],
            'onboarding_status' => [
                'description' => 'Filter centers by onboarding status.',
                'example' => 'ACTIVE',
            ],
            'search' => [
                'description' => 'Search centers by name or slug.',
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
            'updated_from' => [
                'description' => 'Filter centers updated from the given date.',
                'example' => '2024-12-01',
            ],
            'updated_to' => [
                'description' => 'Filter centers updated up to the given date.',
                'example' => '2024-12-31',
            ],
            'deleted' => [
                'description' => 'Deleted filter mode: active, with_deleted, or only_deleted.',
                'example' => 'active',
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
