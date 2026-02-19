<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Devices;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkApproveDeviceChangeRequestsRequest extends FormRequest
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
            'new_device_id' => ['sometimes', 'string'],
            'new_model' => ['sometimes', 'string'],
            'new_os_version' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'request_ids' => [
                'description' => 'Device change request IDs to approve.',
                'example' => [501, 502, 503],
            ],
            'new_device_id' => [
                'description' => 'Optional shared device identifier override.',
                'example' => 'new-device-uuid',
            ],
            'new_model' => [
                'description' => 'Optional shared model override.',
                'example' => 'iPhone 15',
            ],
            'new_os_version' => [
                'description' => 'Optional shared OS version override.',
                'example' => 'iOS 17.2',
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
