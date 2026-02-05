<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight category representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Category
 */
class CategorySummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'title' => $category->translate('title'),
        ];
    }
}
