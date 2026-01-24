<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Categories;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'center_id' => $category->center_id,
            'title' => $category->translate('title'),
            'description' => $category->translate('description'),
            'title_translations' => $category->title_translations,
            'description_translations' => $category->description_translations,
            'parent_id' => $category->parent_id,
            'order_index' => $category->order_index,
            'is_active' => $category->is_active,
        ];
    }
}
