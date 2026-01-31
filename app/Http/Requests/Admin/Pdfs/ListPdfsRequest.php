<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use App\Filters\Admin\PdfFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListPdfsRequest extends AdminListRequest
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
            'course_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string'],
        ]);
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

    public function filters(): PdfFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new PdfFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search')
        );
    }
}
