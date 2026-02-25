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
            'is_published' => $section->visible,
            'videos_count' => $this->resolveRelationCount('videos'),
            'pdfs_count' => $this->resolveRelationCount('pdfs'),
            'created_at' => $section->created_at,
            'updated_at' => $section->updated_at,
        ];
    }

    private function resolveRelationCount(string $relation): int
    {
        /** @var Section $section */
        $section = $this->resource;

        $countAttribute = $relation.'_count';
        if (isset($section->{$countAttribute}) && is_numeric($section->{$countAttribute})) {
            return (int) $section->{$countAttribute};
        }

        if ($section->relationLoaded($relation)) {
            /** @var \Illuminate\Support\Collection<int, mixed> $related */
            $related = $section->{$relation};

            return $related->count();
        }

        return 0;
    }
}
