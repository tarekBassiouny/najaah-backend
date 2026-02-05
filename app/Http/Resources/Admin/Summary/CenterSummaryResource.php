<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight center representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Center
 */
class CenterSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Center $center */
        $center = $this->resource;

        return [
            'id' => $center->id,
            'name' => $center->translate('name'),
            'slug' => $center->slug,
        ];
    }
}
