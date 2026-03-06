<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Education;

use App\Enums\EducationalStage;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Grade
 */
class GradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Grade $grade */
        $grade = $this->resource;
        $stage = $grade->stage instanceof EducationalStage ? $grade->stage : EducationalStage::from((int) $grade->stage);

        return [
            'id' => $grade->id,
            'center_id' => $grade->center_id,
            'name' => $grade->translate('name'),
            'name_translations' => $grade->name_translations,
            'slug' => $grade->slug,
            'stage' => $stage->value,
            'stage_label' => $stage->label(),
            'order' => $grade->order,
            'is_active' => $grade->is_active,
            'students_count' => $grade->students_count,
            'created_at' => $grade->created_at,
            'updated_at' => $grade->updated_at,
        ];
    }
}
