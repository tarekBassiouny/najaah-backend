<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Http\Resources\CategoryResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class ExploreCourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;

        return [
            'id' => $course->id,
            'title' => $course->translate('title'),
            'description' => $course->translate('description'),
            'difficulty' => $course->difficulty_level ?? null,
            'language' => $course->language,
            'is_featured' => $course->is_featured,
            'is_enrolled' => (bool) ($course->is_enrolled ?? false),
            'thumbnail' => $course->thumbnail_url ?? null,
            'status' => $course->status,
            'published_at' => $course->publish_at,
            'duration_minutes' => $course->duration_minutes,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'center' => new CenterResource($this->whenLoaded('center')),
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),

        ];
    }
}
