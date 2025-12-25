<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'phone' => ['required', 'string'],
            'country_code' => ['required', 'string', 'max:5'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'phone' => [
                'description' => 'The full phone number including country code.',
                'example' => '01234567890',
            ],
            'country_code' => [
                'description' => 'The country dialing code.',
                'example' => '+2',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/\D+/', '', (string) $this->input('phone')),
            'country_code' => trim((string) $this->input('country_code')),
        ]);
    }
}
