<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight instructor representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Instructor
 */
class InstructorSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Instructor $instructor */
        $instructor = $this->resource;

        return [
            'id' => $instructor->id,
            'name' => $instructor->translate('name'),
        ];
    }
}
