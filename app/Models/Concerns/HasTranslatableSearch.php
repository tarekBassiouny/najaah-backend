<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasTranslatableSearch
{
    /**
     * Scope: search in translated JSON columns.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $attributes  ['name', 'title']
     * @param  array<int, string>|null  $locales
     * @return Builder<TModel>
     */
    public function scopeWhereTranslationLike(
        Builder $query,
        array $attributes,
        string $term,
        ?array $locales = null
    ): Builder {
        $locales ??= [app()->getLocale()];
        $term = mb_strtolower($term);

        return $query->where(function (Builder $q) use ($attributes, $term, $locales): void {
            foreach ($attributes as $attribute) {
                foreach ($locales as $locale) {
                    $q->orWhereRaw(
                        sprintf("LOWER(json_unquote(json_extract(%s_translations, '\$.\"%s\"'))) LIKE ?", $attribute, $locale),
                        [sprintf('%%%s%%', $term)]
                    );
                }
            }
        });
    }
}
