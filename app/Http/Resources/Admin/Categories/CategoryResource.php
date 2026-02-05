<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Categories;

use App\Http\Resources\Admin\Summary\CategorySummaryResource;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
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
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'title' => $category->translate('title'),
            'description' => $category->translate('description'),
            'title_translations' => $category->title_translations,
            'description_translations' => $category->description_translations,
            'parent' => new CategorySummaryResource($this->whenLoaded('parent')),
            'order_index' => $category->order_index,
            'is_active' => $category->is_active,
        ];
    }
}
