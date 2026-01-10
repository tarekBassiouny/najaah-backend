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
            'phone' => ['required', 'string', 'regex:/^[1-9][0-9]*$/'],
            'country_code' => ['required', 'string', 'max:8', 'regex:/^(\+\d{1,6}|00\d{1,6})$/'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'phone' => [
                'description' => 'Subscriber number without leading zero.',
                'example' => '123456789',
            ],
            'country_code' => [
                'description' => 'Dialing code with + or 00 prefix.',
                'example' => '+966',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/\D+/', '', (string) $this->input('phone'));
        $countryCode = preg_replace('/[^\d+]+/', '', (string) $this->input('country_code'));

        $this->merge([
            'phone' => $phone,
            'country_code' => trim((string) $countryCode),
        ]);
    }
}
