<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PlaybackSessions;

use App\Filters\Admin\PlaybackSessionFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

final class ListPlaybackSessionsRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'is_full_play',
            'is_locked',
            'auto_closed',
            'is_active',
        ];

        $payload = [];
        foreach ($booleanFields as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = filter_var(
                $this->input($field),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($value !== null) {
                $payload[$field] = $value;
            }
        }

        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>|string>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'video_id' => ['sometimes', 'integer', 'exists:videos,id'],
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'is_full_play' => ['sometimes', 'boolean'],
            'is_locked' => ['sometimes', 'boolean'],
            'auto_closed' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:255'],
            'started_from' => ['sometimes', 'date'],
            'started_to' => ['sometimes', 'date'],
            'order_by' => ['sometimes', 'string', Rule::in(['started_at', 'updated_at', 'progress_percent', 'watch_duration'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '25',
            ],
            'user_id' => [
                'description' => 'Filter sessions for a specific student.',
                'example' => '42',
            ],
            'video_id' => [
                'description' => 'Filter sessions by video ID.',
                'example' => '10',
            ],
            'course_id' => [
                'description' => 'Filter sessions by course ID.',
                'example' => '5',
            ],
            'search' => [
                'description' => 'Search users by name/email/phone or video by title within the center.',
                'example' => 'physics',
            ],
            'is_full_play' => [
                'description' => 'Filter only fully played sessions (true/false).',
                'example' => 'true',
            ],
            'is_locked' => [
                'description' => 'Filter locked sessions (true/false).',
                'example' => 'false',
            ],
            'auto_closed' => [
                'description' => 'Filter sessions auto-closed due to timeout or limit (true/false).',
                'example' => 'true',
            ],
            'is_active' => [
                'description' => 'Filter active sessions (ended_at is null).',
                'example' => 'true',
            ],
            'started_from' => [
                'description' => 'Filter sessions that started on or after this date.',
                'example' => '2026-02-19',
            ],
            'started_to' => [
                'description' => 'Filter sessions that started on or before this date.',
                'example' => '2026-02-28',
            ],
            'order_by' => [
                'description' => 'Sort column (started_at|updated_at|progress_percent|watch_duration).',
                'example' => 'started_at',
            ],
            'order_direction' => [
                'description' => 'Sort direction (asc|desc).',
                'example' => 'desc',
            ],
        ];
    }

    public function filters(): PlaybackSessionFilters
    {
        $data = $this->validated();

        return new PlaybackSessionFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            userId: FilterInput::intOrNull($data, 'user_id'),
            videoId: FilterInput::intOrNull($data, 'video_id'),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            isFullPlay: FilterInput::boolOrNull($data, 'is_full_play'),
            isLocked: FilterInput::boolOrNull($data, 'is_locked'),
            autoClosed: FilterInput::boolOrNull($data, 'auto_closed'),
            isActive: FilterInput::boolOrNull($data, 'is_active'),
            search: FilterInput::stringOrNull($data, 'search'),
            startedFrom: FilterInput::stringOrNull($data, 'started_from'),
            startedTo: FilterInput::stringOrNull($data, 'started_to'),
            orderBy: $this->resolveOrderBy($data),
            orderDirection: $this->resolveOrderDirection($data)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOrderBy(array $data): string
    {
        if (isset($data['order_by'])) {
            return (string) $data['order_by'];
        }

        return 'started_at';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOrderDirection(array $data): string
    {
        if (isset($data['order_direction']) && $data['order_direction'] === 'asc') {
            return 'asc';
        }

        return 'desc';
    }
}
