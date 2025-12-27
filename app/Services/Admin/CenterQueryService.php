<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Center;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CenterQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Center>
     */
    public function build(array $filters): Builder
    {
        $query = Center::query()
            ->with('setting')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if (isset($filters['slug']) && is_string($filters['slug'])) {
            $query->where('slug', $filters['slug']);
        }

        if (isset($filters['type']) && is_numeric($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }

        if (isset($filters['tier']) && is_numeric($filters['tier'])) {
            $query->where('tier', (int) $filters['tier']);
        }

        if (array_key_exists('is_featured', $filters)) {
            $value = $filters['is_featured'];
            $isFeatured = null;

            if (is_bool($value)) {
                $isFeatured = $value;
            } elseif (is_numeric($value)) {
                $isFeatured = (int) $value === 1;
            } elseif (is_string($value)) {
                $isFeatured = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            if ($isFeatured !== null) {
                $query->where('is_featured', $isFeatured);
            }
        }

        if (isset($filters['onboarding_status']) && is_string($filters['onboarding_status'])) {
            $query->where('onboarding_status', $filters['onboarding_status']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('name_translations', 'like', '%'.$term.'%');
            }
        }

        if (isset($filters['created_from']) && is_string($filters['created_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['created_from'])->startOfDay());
        }

        if (isset($filters['created_to']) && is_string($filters['created_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['created_to'])->endOfDay());
        }

        return $query;
    }
}
