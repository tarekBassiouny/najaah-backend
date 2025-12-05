<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'device_uuid' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
            'device_os' => ['nullable', 'string'],
            'device_type' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'otp' => [
                'description' => 'The OTP code received by the user.',
                'example' => '123456',
            ],
            'token' => [
                'description' => 'The OTP token returned from send-otp.',
                'example' => 'e9f9c844-0e58-4aab-8db0-0c5b0aeb8d99',
            ],
            'device_uuid' => [
                'description' => 'The unique device identifier.',
                'example' => 'device-123',
            ],
            'device_name' => [
                'description' => 'The human readable device name.',
                'example' => 'iPhone 15',
            ],
            'device_os' => [
                'description' => 'The OS or platform info.',
                'example' => 'iOS 17',
            ],
            'device_type' => [
                'description' => 'The type of device.',
                'example' => 'mobile',
            ],
        ];
    }
}
