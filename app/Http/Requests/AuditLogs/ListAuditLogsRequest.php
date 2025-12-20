<?php

declare(strict_types=1);

namespace App\Http\Requests\AuditLogs;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListAuditLogsRequest extends FormRequest
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
        return [
            'entity_type' => ['sometimes', 'string', 'max:255'],
            'entity_id' => ['sometimes', 'integer'],
            'action' => ['sometimes', 'string', 'max:255'],
            'user_id' => ['sometimes', 'integer'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'entity_type' => [
                'description' => 'Filter by entity class/type.',
                'example' => 'App\\Models\\Course',
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
