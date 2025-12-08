<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class CenterResource extends JsonResource
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
            'name' => $center->name,
            'description' => $center->description,
        ];
    }
}
