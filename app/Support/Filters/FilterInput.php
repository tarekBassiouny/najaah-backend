<?php

declare(strict_types=1);

namespace App\Support\Filters;

class FilterInput
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function intOrNull(array $data, string $key): ?int
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        return is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function stringOrNull(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = trim((string) $data[$key]);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function boolOrNull(array $data, string $key): ?bool
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function page(array $data, int $default = 1): int
    {
        return self::intOrNull($data, 'page') ?? $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function perPage(array $data, int $default = 15): int
    {
        return self::intOrNull($data, 'per_page') ?? $default;
    }
}
