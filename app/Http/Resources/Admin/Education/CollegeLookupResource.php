<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Education;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin College
 */
class CollegeLookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var College $college */
        $college = $this->resource;

        return [
            'id' => $college->id,
            'name' => $college->translate('name'),
            'is_active' => $college->is_active,
        ];
    }
}
