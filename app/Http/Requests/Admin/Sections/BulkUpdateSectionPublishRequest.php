<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Sections;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateSectionPublishRequest extends FormRequest
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
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'section_ids' => [
                'description' => 'Section IDs to publish/unpublish in bulk.',
                'example' => [1, 2, 3],
            ],
            'section_ids.*' => [
                'description' => 'Section ID.',
                'example' => 1,
            ],
        ];
    }
}
