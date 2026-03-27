<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Phone\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PhoneSearch
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     */
    public function applyUserPhoneLike(Builder $builder, string $term, string $boolean = 'or'): void
    {
        $terms = $this->terms($term);

        foreach ($terms as $value) {
            $builder->where(function (Builder $query) use ($value, $boolean): void {
                $like = '%'.$value.'%';

                $query->where('phone', 'like', $like, $boolean)
                    ->orWhere('phone_normalized', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT(REPLACE(REPLACE(COALESCE(country_code, ''), '+', ''), '00', ''), COALESCE(phone, '')) like ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT('0', COALESCE(phone, '')) like ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "REPLACE(COALESCE(phone_normalized, ''), '+', '') like ?",
                        [$like]
                    );
            }, null, null, $boolean);
        }
    }

    /**
     * @return string[]
     */
    public function terms(string $term): array
    {
        $digits = preg_replace('/\D+/', '', $term) ?: '';

        if ($digits === '') {
            return [];
        }

        $terms = [$digits];

        if (str_starts_with($digits, '00')) {
            $terms[] = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $terms[] = ltrim($digits, '0');

            if (str_starts_with($digits, '01')) {
                $terms[] = '20'.substr($digits, 1);
            }
        }

        if (str_starts_with($digits, '20')) {
            $terms[] = '0'.substr($digits, 2);
        }

        $normalized = $this->phoneNormalizer->normalize($term);
        if ($normalized !== null) {
            $terms[] = $normalized;
            $terms[] = ltrim($normalized, '+');
        }

        return array_values(array_unique(array_filter($terms, static fn (string $value): bool => $value !== '')));
    }
}
