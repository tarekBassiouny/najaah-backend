<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceChangeRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'reason' => [
                'description' => 'Optional reason for requesting a device change.',
                'example' => 'Lost my phone and need to register a new device.',
            ],
        ];
    }
}
