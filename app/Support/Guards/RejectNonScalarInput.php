<?php

declare(strict_types=1);

namespace App\Support\Guards;

use Illuminate\Validation\ValidationException;

class RejectNonScalarInput
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     */
    public static function validate(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($value === null) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                self::reject($field);
            }

            if (is_string($value) && self::isJsonObjectOrArray($value)) {
                self::reject($field);
            }
        }
    }

    private static function isJsonObjectOrArray(string $value): bool
    {
        $trimmed = trim($value);

        if ($trimmed === '' || (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '['))) {
            return false;
        }

        $decoded = json_decode($trimmed, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    private static function reject(string $field): void
    {
        throw ValidationException::withMessages([
            $field => ['Translations must be plain strings.'],
        ]);
    }
}
