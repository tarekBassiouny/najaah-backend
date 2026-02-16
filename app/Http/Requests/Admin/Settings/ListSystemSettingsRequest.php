<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Filters\Admin\SystemSettingFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListSystemSettingsRequest extends AdminListRequest
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
            'search' => ['sometimes', 'string'],
            'is_public' => ['sometimes', 'boolean'],
        ]);
    }

    public function filters(): SystemSettingFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new SystemSettingFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            search: FilterInput::stringOrNull($data, 'search'),
            isPublic: FilterInput::boolOrNull($data, 'is_public')
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
                'description' => 'Search settings by key.',
                'example' => 'support',
            ],
            'is_public' => [
                'description' => 'Filter by public visibility.',
                'example' => '1',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawIsPublic = $this->input('is_public');
        if (! is_string($rawIsPublic)) {
            return;
        }

        $normalized = filter_var($rawIsPublic, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return;
        }

        $this->merge([
            'is_public' => $normalized,
        ]);
    }
}
