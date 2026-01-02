<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Filters\Admin\CenterFilters;
use Illuminate\Foundation\Http\FormRequest;

class ListCentersRequest extends FormRequest
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
            'slug' => ['sometimes', 'string'],
            'type' => ['sometimes', 'integer'],
            'tier' => ['sometimes', 'integer'],
            'is_featured' => ['sometimes', 'boolean'],
            'onboarding_status' => ['sometimes', 'string'],
            'search' => ['sometimes', 'string'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
        ];
    }

    public function filters(): CenterFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $term = isset($data['search']) ? trim((string) $data['search']) : null;

        $isFeatured = null;
        if (array_key_exists('is_featured', $data)) {
            $isFeatured = filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new CenterFilters(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            type: isset($data['type']) ? (int) $data['type'] : null,
            tier: isset($data['tier']) ? (int) $data['tier'] : null,
            isFeatured: $isFeatured,
            onboardingStatus: isset($data['onboarding_status']) ? (string) $data['onboarding_status'] : null,
            search: $term !== '' ? $term : null,
            createdFrom: isset($data['created_from']) ? (string) $data['created_from'] : null,
            createdTo: isset($data['created_to']) ? (string) $data['created_to'] : null
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
