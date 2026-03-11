<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\LearningAsset;

use App\Models\LearningAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LearningAsset
 */
class LearningAssetListResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_type' => $this->asset_type->value,
            'title' => $this->translate('title'),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
