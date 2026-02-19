<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraViews;

use App\Filters\Admin\ExtraViewRequestFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListExtraViewRequestsRequest extends AdminListRequest
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
            'status' => ['sometimes', 'string', 'in:PENDING,APPROVED,REJECTED'],
            'center_id' => ['sometimes', 'integer'],
            'user_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:255'],
            'course_id' => ['sometimes', 'integer'],
            'course_title' => ['sometimes', 'string', 'max:255'],
            'video_id' => ['sometimes', 'integer'],
            'video_title' => ['sometimes', 'string', 'max:255'],
            'decided_by' => ['sometimes', 'integer'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'requested_at_from' => ['sometimes', 'date'],
            'requested_at_to' => ['sometimes', 'date'],
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
            'center_id' => [
                'description' => 'Filter by center ID (system scope only).',
                'example' => '2',
            ],
            'status' => [
                'description' => 'Filter by request status.',
                'example' => 'PENDING',
            ],
            'user_id' => [
                'description' => 'Filter by user ID.',
                'example' => '5',
            ],
            'search' => [
                'description' => 'Search by student name, phone, or email (partial match).',
                'example' => '0101',
            ],
            'course_id' => [
                'description' => 'Filter by course ID.',
                'example' => '12',
            ],
            'course_title' => [
                'description' => 'Search by course title (partial match).',
                'example' => 'Physics',
            ],
            'video_id' => [
                'description' => 'Filter by video ID.',
                'example' => '44',
            ],
            'video_title' => [
                'description' => 'Search by video title (partial match).',
                'example' => 'Lesson 1',
            ],
            'decided_by' => [
                'description' => 'Filter by decider admin ID.',
                'example' => '8',
            ],
            'date_from' => [
                'description' => 'Filter requests created from this date.',
                'example' => '2025-01-01',
            ],
            'date_to' => [
                'description' => 'Filter requests created up to this date.',
                'example' => '2025-12-31',
            ],
            'requested_at_from' => [
                'description' => 'Alias of date_from for request creation date.',
                'example' => '2025-01-01',
            ],
            'requested_at_to' => [
                'description' => 'Alias of date_to for request creation date.',
                'example' => '2025-12-31',
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

    public function filters(): ExtraViewRequestFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $dateFrom = FilterInput::stringOrNull($data, 'requested_at_from') ?? FilterInput::stringOrNull($data, 'date_from');
        $dateTo = FilterInput::stringOrNull($data, 'requested_at_to') ?? FilterInput::stringOrNull($data, 'date_to');

        return new ExtraViewRequestFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            status: FilterInput::stringOrNull($data, 'status'),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            userId: FilterInput::intOrNull($data, 'user_id'),
            search: FilterInput::stringOrNull($data, 'search'),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            courseTitle: FilterInput::stringOrNull($data, 'course_title'),
            videoId: FilterInput::intOrNull($data, 'video_id'),
            videoTitle: FilterInput::stringOrNull($data, 'video_title'),
            decidedBy: FilterInput::intOrNull($data, 'decided_by'),
            dateFrom: $dateFrom,
            dateTo: $dateTo
        );
    }
}
