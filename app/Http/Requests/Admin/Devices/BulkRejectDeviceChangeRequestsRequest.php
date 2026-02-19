<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Devices;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkRejectDeviceChangeRequestsRequest extends FormRequest
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
                'description' => 'Device change request IDs to reject.',
                'example' => [501, 502, 503],
            ],
            'decision_reason' => [
                'description' => 'Optional decision reason.',
                'example' => 'Rejected after investigation.',
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
