<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Http\Resources\CategoryResource;
use App\Models\Course;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Course
 */
class CenterListCourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Course $course */
        $course = $this->resource;
        $thumbnailUrlResolver = app(CourseThumbnailUrlResolver::class);

        return [
            'id' => $course->id,
            'title' => $course->translate('title'),
            'description' => $course->translate('description'),
            'difficulty' => $course->difficulty_level ?? null,
            'language' => $course->language,
            'is_featured' => $course->is_featured,
            'is_enrolled' => (bool) ($course->is_enrolled ?? false),
            'thumbnail' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'published_at' => $course->publish_at,
            'duration_minutes' => $course->duration_minutes,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
