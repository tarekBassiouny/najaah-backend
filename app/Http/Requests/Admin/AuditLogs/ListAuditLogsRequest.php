<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuditLogs;

use App\Filters\Admin\AuditLogFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Models\Course;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListAuditLogsRequest extends AdminListRequest
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
            'center_id' => ['sometimes', 'integer'],
            'entity_type' => ['sometimes', 'string', 'max:255'],
            'entity_id' => ['sometimes', 'integer'],
            'action' => ['sometimes', 'string', 'max:255'],
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
            'center_id' => [
                'description' => 'Filter by center ID (super admin only).',
                'example' => '2',
            ],
            'entity_type' => [
                'description' => 'Filter by entity class/type.',
                'example' => Course::class,
            ],
            'entity_id' => [
                'description' => 'Filter by specific entity id.',
                'example' => '12',
            ],
            'action' => [
                'description' => 'Filter by audit action.',
                'example' => 'enrollment_created',
            ],
            'user_id' => [
                'description' => 'Filter by actor user id.',
                'example' => '3',
            ],
            'date_from' => [
                'description' => 'Filter logs starting from this date.',
                'example' => '2025-01-01',
            ],
            'date_to' => [
                'description' => 'Filter logs up to this date.',
                'example' => '2025-12-31',
            ],
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '20',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
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

    public function filters(): AuditLogFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new AuditLogFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            entityType: FilterInput::stringOrNull($data, 'entity_type'),
            entityId: FilterInput::intOrNull($data, 'entity_id'),
            action: FilterInput::stringOrNull($data, 'action'),
            userId: FilterInput::intOrNull($data, 'user_id'),
            dateFrom: FilterInput::stringOrNull($data, 'date_from'),
            dateTo: FilterInput::stringOrNull($data, 'date_to')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
