<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use App\Enums\VideoAccessCodeStatus;
use App\Filters\Admin\VideoAccessCodeFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListVideoAccessCodesRequest extends AdminListRequest
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
            'status' => ['sometimes', 'string', 'in:active,used,revoked,expired'],
            'user_id' => ['sometimes', 'integer'],
            'video_id' => ['sometimes', 'integer'],
            'course_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);
    }

    public function filters(): VideoAccessCodeFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new VideoAccessCodeFilters(
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
            'active' => VideoAccessCodeStatus::Active->value,
            'used' => VideoAccessCodeStatus::Used->value,
            'revoked' => VideoAccessCodeStatus::Revoked->value,
            'expired' => VideoAccessCodeStatus::Expired->value,
            default => null,
        };
    }
}
