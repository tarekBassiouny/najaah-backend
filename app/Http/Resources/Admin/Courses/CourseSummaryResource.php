<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Models\Course;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Lightweight course representation for listings.
 *
 * @mixin Course
 */
class CourseSummaryResource extends JsonResource
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
            'language' => $course->language,
            'thumbnail' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'is_published' => (bool) $course->is_published,
            'published_at' => $course->publish_at,
        ];
    }
}
