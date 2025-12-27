<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use Illuminate\Foundation\Http\FormRequest;

class ListStudentsRequest extends FormRequest
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
            'center_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
            'search' => ['sometimes', 'string'],
        ];
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
            'search' => [
                'description' => 'Search students by name, username, or email.',
                'example' => 'Sara',
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
