<?php

declare(strict_types=1);

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class ReorderSectionsRequest extends FormRequest
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
            'sections' => ['required', 'array'],
            'sections.*' => ['integer', 'exists:sections,id'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'sections' => [
                'description' => 'Ordered list of section IDs for the course.',
                'example' => '[1,2,3]',
            ],
        ];
    }
}
