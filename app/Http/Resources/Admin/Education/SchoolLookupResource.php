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
class SchoolLookupResource extends JsonResource
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
            'name' => $school->translate('name'),
            'type' => $type->value,
            'type_label' => $type->label(),
            'is_active' => $school->is_active,
        ];
    }
}
