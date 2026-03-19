<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningAssetProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => data_get($this->resource, 'status'),
            'status_label' => data_get($this->resource, 'status_label'),
            'progress_percent' => data_get($this->resource, 'progress_percent'),
            'is_completed' => data_get($this->resource, 'is_completed'),
            'state' => data_get($this->resource, 'state'),
            'started_at' => data_get($this->resource, 'started_at'),
            'last_interacted_at' => data_get($this->resource, 'last_interacted_at'),
            'completed_at' => data_get($this->resource, 'completed_at'),
        ];
    }
}
