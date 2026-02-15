<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\SurveyScopeType;
use App\Filters\Admin\SurveyFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

class ListSurveysRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'scope_type' => ['sometimes', 'integer', Rule::in(array_column(SurveyScopeType::cases(), 'value'))],
            'center_id' => ['sometimes', 'integer', 'exists:centers,id'],
            'is_active' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'integer'],
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => [
                'description' => 'Items per page (max 50, recommended 20 for listing/infinite scroll).',
                'example' => '20',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'is_active' => [
                'description' => 'Filter surveys by active state (1 active, 0 inactive).',
                'example' => '1',
            ],
            'scope_type' => [
                'description' => 'Filter surveys by scope type (1 system, 2 center).',
                'example' => '2',
            ],
            'center_id' => [
                'description' => 'Filter surveys by center ID (super admin only).',
                'example' => '1',
            ],
            'type' => [
                'description' => 'Filter surveys by type (1 feedback, 2 mandatory, 3 poll).',
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

    public function filters(): SurveyFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new SurveyFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            scopeType: FilterInput::intOrNull($data, 'scope_type'),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            isActive: FilterInput::boolOrNull($data, 'is_active'),
            type: FilterInput::intOrNull($data, 'type')
        );
    }
}
