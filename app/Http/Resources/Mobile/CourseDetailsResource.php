<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Http\Resources\CategoryResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Course
 */
class CourseDetailsResource extends JsonResource
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
            'thumbnail' => $course->thumbnail_url ?? null,
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'is_enrolled' => (bool) ($course->is_enrolled ?? false),
            'enrollment_status' => $course->enrollment_status ?? null,
            'published_at' => $course->publish_at,
            'duration_minutes' => $course->duration_minutes,
            'primary_instructor_id' => $course->primary_instructor_id,
            'center' => new CenterResource($this->whenLoaded('center')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
            'sections' => CourseSectionResource::collection($this->whenLoaded('sections')),
            'videos' => CourseVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => CoursePdfResource::collection($this->whenLoaded('pdfs')),
        ];
    }
}
