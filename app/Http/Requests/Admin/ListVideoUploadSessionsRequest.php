<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListVideoUploadSessionsRequest extends FormRequest
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
            'status' => ['sometimes', 'integer'],
            'center_id' => ['sometimes', 'integer'],
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
            'status' => [
                'description' => 'Filter by upload status (0-4).',
                'example' => '3',
            ],
            'center_id' => [
                'description' => 'Filter by center ID (admins scoped automatically).',
                'example' => '1',
            ],
        ];
    }
}
