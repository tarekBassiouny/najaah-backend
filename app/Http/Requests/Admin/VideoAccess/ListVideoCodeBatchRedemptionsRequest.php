<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use App\Http\Requests\Admin\AdminListRequest;

class ListVideoCodeBatchRedemptionsRequest extends AdminListRequest
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
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'Page number for pagination.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Number of items per page.',
                'example' => 15,
            ],
            'search' => [
                'description' => 'Search by student name, email, or code.',
                'example' => 'john@example.com',
            ],
        ];
    }
}
