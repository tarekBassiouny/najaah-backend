<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Videos;

use App\Filters\Admin\VideoUploadSessionFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListVideoUploadSessionsRequest extends AdminListRequest
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
            'status' => ['sometimes', 'string', 'in:all,active,terminal,pending,uploading,processing,ready,failed'],
        ]);
    }

    public function filters(): VideoUploadSessionFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new VideoUploadSessionFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            status: null,
            centerId: null,
            statusKey: FilterInput::stringOrNull($data, 'status')
        );
    }
}
