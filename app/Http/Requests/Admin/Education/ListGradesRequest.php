<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Education;

use App\Http\Requests\Admin\AdminListRequest;

class ListGradesRequest extends AdminListRequest
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
            'search' => ['sometimes', 'string'],
            'stage' => ['sometimes', 'integer', 'in:0,1,2,3,4'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => ['description' => 'Items per page (max 100).', 'example' => '15'],
            'page' => ['description' => 'Page number to retrieve.', 'example' => '1'],
            'search' => ['description' => 'Search by localized grade name.', 'example' => 'Grade 9'],
            'stage' => ['description' => 'Educational stage filter.', 'example' => '2'],
            'is_active' => ['description' => 'Active status filter.', 'example' => 'true'],
        ];
    }
}
