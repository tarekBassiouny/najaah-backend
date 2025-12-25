<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ShowCenterRequest extends FormRequest
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
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
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
