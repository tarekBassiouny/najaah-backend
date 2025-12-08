<?php

declare(strict_types=1);

namespace App\Http\Requests\Sections;

use Illuminate\Foundation\Http\FormRequest;

class ReorderSectionRequest extends FormRequest
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
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['integer', 'exists:sections,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'sections' => [
                'description' => 'Ordered list of section IDs in their desired order.',
                'example' => [2, 1, 3],
            ],
        ];
    }
}
