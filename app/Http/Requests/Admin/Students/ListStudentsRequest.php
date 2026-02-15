<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use App\Enums\CenterType;
use App\Filters\Admin\StudentFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

class ListStudentsRequest extends AdminListRequest
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
            'center_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
            'type' => ['sometimes', 'string', Rule::in(['branded', 'unbranded', '0', '1'])],
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
            'type' => [
                'description' => 'Filter students by center assignment (branded => center_id not null, unbranded => center_id null).',
                'example' => 'branded',
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
            search: FilterInput::stringOrNull($data, 'search'),
            centerType: $this->resolveType($data)
        );
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
