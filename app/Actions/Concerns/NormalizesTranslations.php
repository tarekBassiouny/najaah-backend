<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

trait NormalizesTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $translationFields
     * @param  array<string, array<string, string>|null>  $existingTranslations
     * @return array<string, mixed>
     */
    protected function normalizeTranslations(array $data, array $translationFields, array $existingTranslations = [], string $languageKey = 'language'): array
    {
        $language = is_string($data[$languageKey] ?? null) ? (string) $data[$languageKey] : 'en';

        foreach ($translationFields as $field) {
            $baseKey = str_ends_with($field, '_translations') ? substr($field, 0, -strlen('_translations')) : $field;

            $current = $existingTranslations[$field] ?? [];

            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = array_merge($current, $data[$field]);

                continue;
            }

            if (array_key_exists($baseKey, $data) && $data[$baseKey] !== null) {
                $data[$field] = array_merge($current, [$language => (string) $data[$baseKey]]);
            }
        }

        return $data;
    }
}
