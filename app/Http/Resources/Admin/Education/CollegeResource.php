<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Education;

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
            'center_id' => $college->center_id,
            'name' => $college->translate('name'),
            'name_translations' => $college->name_translations,
            'slug' => $college->slug,
            'type' => $college->type,
            'address' => $college->address,
            'is_active' => $college->is_active,
            'students_count' => $college->students_count,
            'created_at' => $college->created_at,
            'updated_at' => $college->updated_at,
        ];
    }
}
