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
class GradeLookupResource extends JsonResource
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
            'name' => $grade->translate('name'),
            'stage' => $stage->value,
            'stage_label' => $stage->label(),
            'is_active' => $grade->is_active,
        ];
    }
}
