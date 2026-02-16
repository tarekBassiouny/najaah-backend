<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Enums\CenterType;
use App\Filters\Admin\CenterFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

class ListCenterOptionsRequest extends AdminListRequest
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
            'search' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string', Rule::in(['branded', 'unbranded', '0', '1'])],
        ]);
    }

    public function filters(): CenterFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CenterFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            slug: null,
            type: $this->resolveType($data),
            tier: null,
            isFeatured: null,
            onboardingStatus: null,
            search: FilterInput::stringOrNull($data, 'search'),
            createdFrom: null,
            createdTo: null
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
                'example' => '20',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'search' => [
                'description' => 'Search centers by name or slug.',
                'example' => 'center-a',
            ],
            'type' => [
                'description' => 'Optional center type filter (branded/unbranded).',
                'example' => 'branded',
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveType(array $data): ?int
    {
        $type = FilterInput::stringOrNull($data, 'type');
        if ($type === null) {
            return null;
        }

        return match ($type) {
            'branded' => CenterType::Branded->value,
            'unbranded' => CenterType::Unbranded->value,
            default => is_numeric($type) ? (int) $type : null,
        };
    }
}
