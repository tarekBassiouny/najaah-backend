<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Education;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin College
 */
class CollegeResource extends JsonResource
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
        ];
    }
}
