<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\SurveyScopeType;
use App\Enums\SurveyType;
use App\Filters\Admin\SurveyFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;
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
            'is_mandatory' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'integer', Rule::in(array_column(SurveyType::cases(), 'value'))],
            'search' => ['sometimes', 'string', 'max:255'],
            'start_from' => ['sometimes', 'date_format:Y-m-d'],
            'start_to' => ['sometimes', 'date_format:Y-m-d'],
            'end_from' => ['sometimes', 'date_format:Y-m-d'],
            'end_to' => ['sometimes', 'date_format:Y-m-d'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startFrom = FilterInput::stringOrNull($this->all(), 'start_from');
            $startTo = FilterInput::stringOrNull($this->all(), 'start_to');
            $endFrom = FilterInput::stringOrNull($this->all(), 'end_from');
            $endTo = FilterInput::stringOrNull($this->all(), 'end_to');

            if ($startFrom !== null && $startTo !== null && $startFrom > $startTo) {
                $validator->errors()->add('start_from', 'start_from must be before or equal to start_to.');
            }

            if ($endFrom !== null && $endTo !== null && $endFrom > $endTo) {
                $validator->errors()->add('end_from', 'end_from must be before or equal to end_to.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['is_active', 'is_mandatory'] as $key) {
            $raw = $this->input($key);
            if (! is_string($raw)) {
                continue;
            }

            $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $normalized[$key] = $value;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
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
                'description' => 'Filter surveys by active state (`true|false` or `1|0`).',
                'example' => 'true',
            ],
            'is_mandatory' => [
                'description' => 'Filter surveys by mandatory state (`true|false` or `1|0`).',
                'example' => 'true',
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
            'search' => [
                'description' => 'Search surveys by title (English or Arabic).',
                'example' => 'feedback',
            ],
            'start_from' => [
                'description' => 'Filter surveys with start_at on or after date (YYYY-MM-DD).',
                'example' => '2026-02-01',
            ],
            'start_to' => [
                'description' => 'Filter surveys with start_at on or before date (YYYY-MM-DD).',
                'example' => '2026-02-28',
            ],
            'end_from' => [
                'description' => 'Filter surveys with end_at on or after date (YYYY-MM-DD).',
                'example' => '2026-03-01',
            ],
            'end_to' => [
                'description' => 'Filter surveys with end_at on or before date (YYYY-MM-DD).',
                'example' => '2026-03-31',
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
            isMandatory: FilterInput::boolOrNull($data, 'is_mandatory'),
            type: FilterInput::intOrNull($data, 'type'),
            search: FilterInput::stringOrNull($data, 'search'),
            startFrom: FilterInput::stringOrNull($data, 'start_from'),
            startTo: FilterInput::stringOrNull($data, 'start_to'),
            endFrom: FilterInput::stringOrNull($data, 'end_from'),
            endTo: FilterInput::stringOrNull($data, 'end_to')
        );
    }
}
