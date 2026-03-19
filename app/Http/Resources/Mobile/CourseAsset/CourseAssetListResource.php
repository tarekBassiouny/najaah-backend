<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\CourseAsset;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAssetListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'type' => data_get($this->resource, 'type'),
            'title' => data_get($this->resource, 'title'),
            'description' => data_get($this->resource, 'description'),
            'attachable_type' => data_get($this->resource, 'attachable_type'),
            'attachable_id' => data_get($this->resource, 'attachable_id'),
            'is_available' => data_get($this->resource, 'is_available'),
            'is_required' => data_get($this->resource, 'is_required'),
            'order_index' => data_get($this->resource, 'order_index'),
            'published_at' => data_get($this->resource, 'published_at'),
            'meta' => data_get($this->resource, 'meta', []),
        ];
    }
}
