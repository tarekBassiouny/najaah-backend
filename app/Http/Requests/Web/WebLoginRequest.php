<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WebLoginRequest extends FormRequest
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
            'device_uuid' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string'],
            'device_os' => ['nullable', 'string'],
        ];
    }
}
