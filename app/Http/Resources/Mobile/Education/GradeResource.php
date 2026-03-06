<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Education;

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

        return [
            'id' => $grade->id,
            'name' => $grade->translate('name'),
        ];
    }
}
