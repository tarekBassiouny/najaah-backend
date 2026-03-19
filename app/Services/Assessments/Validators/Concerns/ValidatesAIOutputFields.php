<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators\Concerns;

trait ValidatesAIOutputFields
{
    /**
     * @param  array<int,string>  $errors
     */
    protected function validateTextNode(mixed $value, string $path, string $language, array &$errors): void
    {
        if ($language === 'both') {
            if (! is_array($value)) {
                $errors[] = $path.' must contain both ar and en translations.';

                return;
            }

            foreach (['ar', 'en'] as $locale) {
                $localeValue = $value[$locale] ?? null;
                if (! is_string($localeValue) || trim($localeValue) === '') {
                    $errors[] = $path.'.'.$locale.' must be a non-empty string.';
                }
            }

            return;
        }

        if (is_string($value) && trim($value) !== '') {
            return;
        }

        if (! is_array($value)) {
            $errors[] = $path.' must be a non-empty text field.';

            return;
        }

        $preferred = $value[$language] ?? null;
        if (is_string($preferred) && trim($preferred) !== '') {
            return;
        }

        foreach (['ar', 'en'] as $locale) {
            $candidate = $value[$locale] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                return;
            }
        }

        $errors[] = $path.' must be a non-empty text field.';
    }

    /**
     * @param  array<int,string>  $errors
     * @return array<int,mixed>
     */
    protected function requireArray(mixed $value, string $path, array &$errors, bool $mustBeNonEmpty = true): array
    {
        if (! is_array($value)) {
            $errors[] = $path.' must be an array.';

            return [];
        }

        if ($mustBeNonEmpty && $value === []) {
            $errors[] = $path.' must not be empty.';
        }

        return $value;
    }

    /**
     * @param  array<int,string>  $errors
     */
    protected function validatePositiveNumber(mixed $value, string $path, array &$errors): void
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            $errors[] = $path.' must be a positive number.';
        }
    }

    /**
     * @param  array<int,string>  $errors
     */
    protected function validateNonNegativeNumber(mixed $value, string $path, array &$errors): void
    {
        if (! is_numeric($value) || (float) $value < 0) {
            $errors[] = $path.' must be a non-negative number.';
        }
    }
}
