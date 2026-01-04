<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Section
 */
class CourseSectionResource extends JsonResource
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
            'title' => $section->translate('title'),
            'description' => $section->translate('description'),
            'order' => $section->order_index,
            'videos' => CourseVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => CoursePdfResource::collection($this->whenLoaded('pdfs')),
        ];
    }
}
