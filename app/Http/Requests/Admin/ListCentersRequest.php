<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListCentersRequest extends FormRequest
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
            'slug' => ['sometimes', 'string'],
            'type' => ['sometimes', 'integer'],
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
            'slug' => [
                'description' => 'Filter centers by slug.',
                'example' => 'center-1',
            ],
            'type' => [
                'description' => 'Filter centers by type.',
                'example' => '1',
            ],
        ];
    }
}
