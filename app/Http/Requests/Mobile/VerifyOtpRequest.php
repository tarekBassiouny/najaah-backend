<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

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
                'example' => '{{otp_code}}',
            ],
            'token' => [
                'description' => 'The OTP token returned from send-otp.',
                'example' => '{{otp_token}}',
            ],
            'device_uuid' => [
                'description' => 'The unique device identifier.',
                'example' => '{{device_uuid}}',
            ],
            'device_name' => [
                'description' => 'The human readable device name.',
                'example' => '{{device_name}}',
            ],
            'device_os' => [
                'description' => 'The OS or platform info.',
                'example' => '{{device_os}}',
            ],
            'device_type' => [
                'description' => 'The type of device.',
                'example' => '{{device_type}}',
            ],
        ];
    }
}
