<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Center;
use Illuminate\Database\Eloquent\Builder;

class CenterQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Center>
     */
    public function build(array $filters): Builder
    {
        $query = Center::query()->with('setting')->orderByDesc('created_at');

        if (isset($filters['slug']) && is_string($filters['slug'])) {
            $query->where('slug', $filters['slug']);
        }

        if (isset($filters['type']) && is_numeric($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('name_translations', 'like', '%'.$term.'%');
            }
        }

        return $query;
    }
}
