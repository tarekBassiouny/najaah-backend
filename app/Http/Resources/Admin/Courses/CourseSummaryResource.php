<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Models\Course;
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

        return [
            'id' => $course->id,
            'title' => $course->translate('title'),
            'description' => $course->translate('description'),
            'language' => $course->language,
            'thumbnail' => $course->thumbnail_url ?? null,
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'published_at' => $course->publish_at,
        ];
    }
}
