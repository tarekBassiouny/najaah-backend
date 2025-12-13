<?php

declare(strict_types=1);

namespace App\Http\Requests\Devices;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDeviceChangeRequestRequest extends FormRequest
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
            'new_device_id' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'os_version' => ['required', 'string', 'max:255'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'new_device_id' => [
                'description' => 'Identifier of the requested new device.',
                'example' => 'device-xyz',
            ],
            'model' => [
                'description' => 'Device model name.',
                'example' => 'iPhone 15',
            ],
            'os_version' => [
                'description' => 'Operating system version.',
                'example' => 'iOS 17',
            ],
            'reason' => [
                'description' => 'Optional reason for requesting device change.',
                'example' => 'Upgraded phone',
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
