<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
        $rawCountryInput = $this->input('country_code', '');
        $rawCountry = is_scalar($rawCountryInput) ? (string) $rawCountryInput : '';
        $countryDigits = preg_replace('/\D+/', '', $rawCountry) ?? '';
        $normalizedDigits = ltrim($countryDigits, '0');
        $normalizedCountry = $normalizedDigits !== '' ? '+'.$normalizedDigits : ($rawCountry !== '' ? $rawCountry : null);

        $rawPhoneInput = $this->input('phone', '');
        $phoneDigits = preg_replace('/\D+/', '', is_scalar($rawPhoneInput) ? (string) $rawPhoneInput : '') ?? '';
        $local = $normalizedDigits !== '' && str_starts_with($phoneDigits, $normalizedDigits)
            ? substr($phoneDigits, strlen($normalizedDigits))
            : $phoneDigits;

        $this->merge([
            'country_code' => $normalizedCountry ?? $rawCountry,
            'phone' => $local,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $rawPhone = $this->input('phone', '');
            $rawCountry = $this->input('country_code', '');
            $phone = is_scalar($rawPhone) ? (string) $rawPhone : '';
            $countryCode = is_scalar($rawCountry) ? (string) $rawCountry : '';

            if (! User::where('phone', $phone)->where('country_code', $countryCode)->exists()) {
                $validator->errors()->add('phone', 'The selected phone is invalid.');
            }
        });
    }
}
