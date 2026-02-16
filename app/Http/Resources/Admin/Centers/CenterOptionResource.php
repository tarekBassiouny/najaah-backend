<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Centers;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Center
 */
class CenterOptionResource extends JsonResource
{
    /**
     * @return array<string, int|string>
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
