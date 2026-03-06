<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\BulkWhatsApp;

use App\Enums\BulkJobStatus;
use App\Filters\Admin\BulkWhatsAppJobFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListBulkWhatsAppJobsRequest extends AdminListRequest
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
            'status' => ['sometimes', 'string', 'in:pending,processing,completed,paused,failed,cancelled'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);
    }

    public function filters(): BulkWhatsAppJobFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new BulkWhatsAppJobFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            status: $this->resolveStatus(FilterInput::stringOrNull($data, 'status')),
            dateFrom: FilterInput::stringOrNull($data, 'date_from'),
            dateTo: FilterInput::stringOrNull($data, 'date_to'),
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'page' => ['description' => 'Page number to retrieve.', 'example' => '1'],
            'per_page' => ['description' => 'Items per page (max 100).', 'example' => '15'],
            'status' => ['description' => 'Bulk WhatsApp job status filter.', 'example' => 'processing'],
            'date_from' => ['description' => 'Start date filter (YYYY-MM-DD).', 'example' => '2026-03-01'],
            'date_to' => ['description' => 'End date filter (YYYY-MM-DD).', 'example' => '2026-03-07'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [];
    }

    private function resolveStatus(?string $status): ?int
    {
        return match ($status) {
            'pending' => BulkJobStatus::Pending->value,
            'processing' => BulkJobStatus::Processing->value,
            'completed' => BulkJobStatus::Completed->value,
            'paused' => BulkJobStatus::Paused->value,
            'failed' => BulkJobStatus::Failed->value,
            'cancelled' => BulkJobStatus::Cancelled->value,
            default => null,
        };
    }
}
