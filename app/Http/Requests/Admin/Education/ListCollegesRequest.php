<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Education;

use App\Http\Requests\Admin\AdminListRequest;

class ListCollegesRequest extends AdminListRequest
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
            'search' => ['description' => 'Search by localized college name.', 'example' => 'Cairo University'],
            'is_active' => ['description' => 'Active status filter.', 'example' => 'true'],
        ];
    }
}
