<?php

declare(strict_types=1);

namespace App\Http\Resources\Sections;

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
            'course_id' => $section->course_id,
            'title' => $section->title,
            'order_index' => $section->order_index,
            'visible' => $section->visible,
        ];
    }
}
