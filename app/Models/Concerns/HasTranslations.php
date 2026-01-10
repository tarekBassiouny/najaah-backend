<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasTranslations
{
    /**
     * @return MorphMany<Translation, self>
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function translate(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $fallback = (string) config('app.fallback_locale');

        $value = $this->resolveTranslationValue($field, $locale);
        if ($value !== null) {
            return $value;
        }

        if ($fallback !== '' && $fallback !== $locale) {
            $fallbackValue = $this->resolveTranslationValue($field, $fallback);
            if ($fallbackValue !== null) {
                return $fallbackValue;
            }
        }

        $base = $this->getAttribute($field);
        $resolved = $this->resolveFromBase($base, $locale, $fallback);
        if ($resolved !== null) {
            return $resolved;
        }

        $legacy = $this->getAttribute($field.'_translations');
        $resolvedLegacy = $this->resolveFromBase($legacy, $locale, $fallback);

        return $resolvedLegacy ?? '';
    }

    private function resolveFromBase(mixed $value, string $locale, string $fallback): ?string
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $this->resolveFromBase($decoded, $locale, $fallback);
                }
            }

            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            $candidate = $value[$locale] ?? null;
            if (is_string($candidate)) {
                return $candidate;
            }

            if ($fallback !== '' && $fallback !== $locale) {
                $fallbackValue = $value[$fallback] ?? null;
                if (is_string($fallbackValue)) {
                    return $fallbackValue;
                }
            }
        }

        return null;
    }

    private function resolveTranslationValue(string $field, string $locale): ?string
    {
        if ($this->relationLoaded('translations')) {
            $translations = $this->getRelation('translations');
            if ($translations instanceof Collection) {
                /** @var Translation|null $translation */
                $translation = $translations->first(
                    static fn (Translation $item): bool => $item->field === $field && $item->locale === $locale
                );
            } else {
                $translation = null;
            }
        } else {
            $translation = $this->translations()
                ->where('field', $field)
                ->where('locale', $locale)
                ->first();
        }

        $value = $translation?->value;

        return is_string($value) ? $value : null;
    }
}
