<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Devices;

use App\Filters\Admin\DeviceChangeRequestFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListDeviceChangeRequestsRequest extends AdminListRequest
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
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
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
            'status' => [
                'description' => 'Filter by request status.',
                'example' => 'PENDING',
            ],
            'center_id' => [
                'description' => 'Filter by center ID (super admin only).',
                'example' => '2',
            ],
            'user_id' => [
                'description' => 'Filter by user ID.',
                'example' => '5',
            ],
            'date_from' => [
                'description' => 'Filter requests created from this date.',
                'example' => '2025-01-01',
            ],
            'date_to' => [
                'description' => 'Filter requests created up to this date.',
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

    public function filters(): DeviceChangeRequestFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new DeviceChangeRequestFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            status: FilterInput::stringOrNull($data, 'status'),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            userId: FilterInput::intOrNull($data, 'user_id'),
            dateFrom: FilterInput::stringOrNull($data, 'date_from'),
            dateTo: FilterInput::stringOrNull($data, 'date_to')
        );
    }
}
