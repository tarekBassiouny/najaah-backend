<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use Illuminate\Foundation\Http\FormRequest;

class ListPdfsRequest extends FormRequest
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
            'course_id' => ['sometimes', 'integer'],
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
            'course_id' => [
                'description' => 'Filter PDFs by course ID.',
                'example' => '10',
            ],
            'search' => [
                'description' => 'Search PDFs by title.',
                'example' => 'Lesson Notes',
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
