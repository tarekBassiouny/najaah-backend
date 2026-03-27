<?php

declare(strict_types=1);

namespace App\Services\Phone;

class PhoneNormalizer
{
    public function normalize(?string $phone, ?string $countryCode = null): ?string
    {
        $normalizedPhone = $this->sanitizePhone($phone);

        if ($normalizedPhone === null) {
            return null;
        }

        if (str_starts_with($normalizedPhone, '00')) {
            $normalizedPhone = '+'.substr($normalizedPhone, 2);
        }

        if (str_starts_with($normalizedPhone, '+')) {
            return $this->normalizeInternational($normalizedPhone);
        }

        $digits = preg_replace('/\D+/', '', $normalizedPhone) ?? '';
        if ($digits === '') {
            return null;
        }

        $countryDigits = $this->sanitizeCountryCode($countryCode);

        if ($countryDigits !== null && $countryDigits !== '') {
            if (str_starts_with($digits, $countryDigits)) {
                return $this->normalizeInternational('+'.$digits);
            }

            if ($countryDigits === '20' && str_starts_with($digits, '01')) {
                return $this->normalizeInternational('+20'.substr($digits, 1));
            }

            return $this->normalizeInternational('+'.$countryDigits.$digits);
        }

        if (str_starts_with($digits, '20')) {
            return $this->normalizeInternational('+'.$digits);
        }

        if (str_starts_with($digits, '01')) {
            return $this->normalizeInternational('+20'.substr($digits, 1));
        }

        return null;
    }

    private function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $sanitized = preg_replace('/[^\d+]+/', '', trim($phone)) ?? '';

        return $sanitized !== '' ? $sanitized : null;
    }

    private function sanitizeCountryCode(?string $countryCode): ?string
    {
        if ($countryCode === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($countryCode)) ?? '';

        return ltrim($digits, '0') !== '' ? ltrim($digits, '0') : null;
    }

    private function normalizeInternational(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        return '+'.$digits;
    }
}
