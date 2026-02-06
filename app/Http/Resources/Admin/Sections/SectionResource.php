<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Sections;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Section
 */
class SectionResource extends JsonResource
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
            'description' => $section->translate('description'),
            'sort_order' => $section->order_index,
            'videos' => SectionVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => SectionPdfResource::collection($this->whenLoaded('pdfs')),
            'created_at' => $section->created_at,
            'updated_at' => $section->updated_at,
        ];
    }
}
