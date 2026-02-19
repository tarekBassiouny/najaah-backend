<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraViews;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkApproveExtraViewRequestsRequest extends FormRequest
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
            'request_ids' => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'distinct'],
            'granted_views' => ['required', 'integer', 'min:1'],
            'decision_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'request_ids' => [
                'description' => 'Extra view request IDs to approve.',
                'example' => [301, 302, 303],
            ],
            'granted_views' => [
                'description' => 'Granted views applied to each request.',
                'example' => 3,
            ],
            'decision_reason' => [
                'description' => 'Optional decision reason.',
                'example' => 'Approved after review.',
            ],
        ];
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
