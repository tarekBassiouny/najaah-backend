<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use App\Filters\Admin\VideoFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListVideosRequest extends AdminListRequest
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
            'status' => ['sometimes', 'string', 'in:0,1,2,3,4,pending,uploading,processing,ready,failed'],
            'source_type' => ['sometimes', 'string', 'in:0,1,url,upload'],
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
                'description' => 'Filter videos by course ID.',
                'example' => '10',
            ],
            'search' => [
                'description' => 'Search videos by title.',
                'example' => 'Intro',
            ],
            'q' => [
                'description' => 'Unified search across title and tags.',
                'example' => 'intro',
            ],
            'status' => [
                'description' => 'Filter by encoding status (pending, uploading, processing, ready, failed) or numeric value (0-4).',
                'example' => 'ready',
            ],
            'source_type' => [
                'description' => 'Filter by source type (upload/url) or numeric value (1/0).',
                'example' => 'upload',
            ],
            'source_provider' => [
                'description' => 'Filter by source provider (e.g. bunny, youtube, vimeo).',
                'example' => 'bunny',
            ],
            'created_from' => [
                'description' => 'Filter videos created on/after this date.',
                'example' => '2026-02-01',
            ],
            'created_to' => [
                'description' => 'Filter videos created on/before this date.',
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

    public function filters(): VideoFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $status = FilterInput::stringOrNull($data, 'status');
        $sourceType = FilterInput::stringOrNull($data, 'source_type');
        $query = FilterInput::stringOrNull($data, 'q');

        $statusValue = null;
        if ($status !== null) {
            $statusMap = [
                'pending' => 0,
                'uploading' => 1,
                'processing' => 2,
                'ready' => 3,
                'failed' => 4,
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

        return new VideoFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: null,
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search'),
            query: $query,
            status: $statusValue,
            sourceType: $sourceTypeValue,
            sourceProvider: FilterInput::stringOrNull($data, 'source_provider'),
            createdFrom: FilterInput::stringOrNull($data, 'created_from'),
            createdTo: FilterInput::stringOrNull($data, 'created_to')
        );
    }
}
