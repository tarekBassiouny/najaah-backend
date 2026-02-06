<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Sections;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight section representation for listings.
 *
 * @mixin Section
 */
class SectionSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Section $section */
        $section = $this->resource;

        return [
            'id' => $section->id,
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'title' => $section->translate('title'),
            'order_index' => $section->order_index,
            'visible' => $section->visible,
        ];
    }
}
