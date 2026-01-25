<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Devices;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApproveDeviceChangeRequest extends FormRequest
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
            'new_device_id' => [
                'description' => 'New device identifier to apply during approval.',
                'example' => 'new-device-uuid',
            ],
            'new_model' => [
                'description' => 'New device model name.',
                'example' => 'iPhone 15',
            ],
            'new_os_version' => [
                'description' => 'New device OS version string.',
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
