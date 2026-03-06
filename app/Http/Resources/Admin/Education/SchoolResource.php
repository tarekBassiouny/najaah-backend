<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Education;

use App\Enums\SchoolType;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin School
 */
class SchoolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var School $school */
        $school = $this->resource;
        $type = $school->type instanceof SchoolType ? $school->type : SchoolType::from((int) $school->type);

        return [
            'id' => $school->id,
            'center_id' => $school->center_id,
            'name' => $school->translate('name'),
            'name_translations' => $school->name_translations,
            'slug' => $school->slug,
            'type' => $type->value,
            'type_label' => $type->label(),
            'address' => $school->address,
            'is_active' => $school->is_active,
            'students_count' => $school->students_count,
            'created_at' => $school->created_at,
            'updated_at' => $school->updated_at,
        ];
    }
}
