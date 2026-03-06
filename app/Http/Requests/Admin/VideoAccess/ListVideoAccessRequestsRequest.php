<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use App\Enums\VideoAccessRequestStatus;
use App\Filters\Admin\VideoAccessRequestFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListVideoAccessRequestsRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'status' => ['sometimes', 'string', 'in:pending,approved,rejected'],
            'user_id' => ['sometimes', 'integer'],
            'video_id' => ['sometimes', 'integer'],
            'course_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);
    }

    public function filters(): VideoAccessRequestFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new VideoAccessRequestFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            status: $this->resolveStatus(FilterInput::stringOrNull($data, 'status')),
            userId: FilterInput::intOrNull($data, 'user_id'),
            videoId: FilterInput::intOrNull($data, 'video_id'),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search'),
            dateFrom: FilterInput::stringOrNull($data, 'date_from'),
            dateTo: FilterInput::stringOrNull($data, 'date_to'),
        );
    }

    private function resolveStatus(?string $status): ?int
    {
        return match ($status) {
            'pending' => VideoAccessRequestStatus::Pending->value,
            'approved' => VideoAccessRequestStatus::Approved->value,
            'rejected' => VideoAccessRequestStatus::Rejected->value,
            default => null,
        };
    }
}
