<?php

declare(strict_types=1);

namespace App\Support\Localization;

class ApiValidationMessageLocalizer
{
    public static function localizeMixed(mixed $value, ?string $locale = null): mixed
    {
        if (is_string($value)) {
            return self::localize($value, $locale);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::localizeMixed($item, $locale);
        }

        return $value;
    }

    public static function localize(string $message, ?string $locale = null): string
    {
        $locale = self::resolveLocale($locale);

        if ($locale !== 'ar') {
            return $message;
        }

        if (preg_match('/^The (.+) field is required\\.$/', $message, $matches) === 1) {
            return self::template('required', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be a string\\.$/', $message, $matches) === 1) {
            return self::template('string', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be an integer\\.$/', $message, $matches) === 1) {
            return self::template('integer', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be a number\\.$/', $message, $matches) === 1) {
            return self::template('numeric', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be true or false\\.$/', $message, $matches) === 1) {
            return self::template('boolean', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be an array\\.$/', $message, $matches) === 1) {
            return self::template('array', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The (.+) field must be a valid email address\\.$/', $message, $matches) === 1) {
            return self::template('email', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (preg_match('/^The selected (.+) is invalid\\.$/', $message, $matches) === 1) {
            return self::template('exists', ['attribute' => self::toAttribute($matches[1])], $locale);
        }

        if (
            preg_match(
                '/^The (.+) field is required when (.+) is (.+)\\.$/',
                $message,
                $matches
            ) === 1
        ) {
            return self::template('required_if', [
                'attribute' => self::toAttribute($matches[1]),
                'other' => self::toAttribute($matches[2]),
                'value' => trim($matches[3]),
            ], $locale);
        }

        if (preg_match('/^The (.+) field must be at least ([0-9]+) characters\\.$/', $message, $matches) === 1) {
            return self::template('min_string', [
                'attribute' => self::toAttribute($matches[1]),
                'min' => trim($matches[2]),
            ], $locale);
        }

        if (
            preg_match('/^The (.+) field must not be greater than ([0-9]+) characters\\.$/', $message, $matches) === 1
        ) {
            return self::template('max_string', [
                'attribute' => self::toAttribute($matches[1]),
                'max' => trim($matches[2]),
            ], $locale);
        }

        if (preg_match('/^The (.+) field must be at least ([0-9]+(?:\\.[0-9]+)?)\\.$/', $message, $matches) === 1) {
            return self::template('min_numeric', [
                'attribute' => self::toAttribute($matches[1]),
                'min' => trim($matches[2]),
            ], $locale);
        }

        if (
            preg_match('/^The (.+) field must not be greater than ([0-9]+(?:\\.[0-9]+)?)\\.$/', $message, $matches) ===
            1
        ) {
            return self::template('max_numeric', [
                'attribute' => self::toAttribute($matches[1]),
                'max' => trim($matches[2]),
            ], $locale);
        }

        return $message;
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function template(string $key, array $replace, string $locale): string
    {
        /** @var array<string, array<string, string>> $templates */
        $templates = config('api_messages.validation.templates', []);
        $message = data_get($templates, sprintf('%s.%s', $locale, $key));

        if (! is_string($message) || $message === '') {
            $message = data_get($templates, 'en.'.$key, '');
        }

        if (! is_string($message) || $message === '') {
            return $key;
        }

        foreach ($replace as $name => $value) {
            $message = str_replace(':'.$name, $value, $message);
        }

        return $message;
    }

    private static function toAttribute(string $raw): string
    {
        return trim(str_replace('_', ' ', $raw));
    }

    private static function resolveLocale(?string $locale): string
    {
        $allowed = ['en', 'ar'];
        $candidate = strtolower(trim($locale ?? (string) app()->getLocale()));

        return in_array($candidate, $allowed, true) ? $candidate : 'en';
    }
}
