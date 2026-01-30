<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitDeviceChangeWithOtpRequest extends FormRequest
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
        return [
            'otp' => ['required', 'string'],
            'token' => ['required', 'string'],
            'device_uuid' => ['required', 'string', 'max:255'],
            'device_model' => ['required', 'string', 'max:255'],
            'device_os' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'otp' => [
                'description' => 'The OTP code sent to the user phone.',
                'example' => '123456',
            ],
            'token' => [
                'description' => 'The OTP token returned from /auth/send-otp.',
                'example' => 'uuid-token-string',
            ],
            'device_uuid' => [
                'description' => 'The unique device identifier for the new device.',
                'example' => 'device-uuid-123',
            ],
            'device_model' => [
                'description' => 'The model name of the new device.',
                'example' => 'iPhone 15 Pro',
            ],
            'device_os' => [
                'description' => 'The operating system version of the new device.',
                'example' => 'iOS 17.2',
            ],
            'reason' => [
                'description' => 'Optional reason for requesting a device change.',
                'example' => 'Lost my phone and need to register a new device.',
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
