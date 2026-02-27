<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pdfs;

use App\Filters\Admin\PdfFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

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
            'q' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['0', '1', '2', '3', 'pending', 'uploading', 'ready', 'failed'])],
            'source_type' => ['sometimes', 'string', Rule::in(['0', '1', 'url', 'upload'])],
            'source_provider' => ['sometimes', 'string', 'max:50'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
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
            'q' => [
                'description' => 'Unified search across title, description, and source identifier.',
                'example' => 'lesson notes',
            ],
            'status' => [
                'description' => 'Filter by upload status (pending, uploading, ready, failed) or numeric value (0-3).',
                'example' => 'ready',
            ],
            'source_type' => [
                'description' => 'Filter by source type (upload/url) or numeric value (1/0).',
                'example' => 'upload',
            ],
            'source_provider' => [
                'description' => 'Filter by source provider (e.g. spaces).',
                'example' => 'spaces',
            ],
            'created_from' => [
                'description' => 'Filter PDFs created on/after this date.',
                'example' => '2026-02-01',
            ],
            'created_to' => [
                'description' => 'Filter PDFs created on/before this date.',
                'example' => '2026-02-28',
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
        $status = FilterInput::stringOrNull($data, 'status');
        $sourceType = FilterInput::stringOrNull($data, 'source_type');

        $statusValue = null;
        if ($status !== null) {
            $statusMap = [
                'pending' => 0,
                'uploading' => 1,
                'ready' => 2,
                'failed' => 3,
            ];
            $statusLower = strtolower($status);
            $statusValue = is_numeric($status) ? (int) $status : ($statusMap[$statusLower] ?? null);
        }

        $sourceTypeValue = null;
        if ($sourceType !== null) {
            $sourceTypeLower = strtolower($sourceType);
            $sourceTypeValue = match (true) {
                is_numeric($sourceType) => (int) $sourceType,
                $sourceTypeLower === 'url' => 0,
                $sourceTypeLower === 'upload' => 1,
                default => null,
            };
        }

        return new PdfFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search'),
            query: FilterInput::stringOrNull($data, 'q'),
            status: $statusValue,
            sourceType: $sourceTypeValue,
            sourceProvider: FilterInput::stringOrNull($data, 'source_provider'),
            createdFrom: FilterInput::stringOrNull($data, 'created_from'),
            createdTo: FilterInput::stringOrNull($data, 'created_to')
        );
    }
}
