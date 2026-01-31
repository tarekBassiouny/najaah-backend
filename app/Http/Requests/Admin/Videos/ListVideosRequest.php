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

        return new VideoFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: null,
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search')
        );
    }
}
