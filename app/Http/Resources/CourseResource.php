<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
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
            'center_id' => $course->center_id,
            'category_id' => $course->category_id,
            'title_translations' => $course->title_translations,
            'description_translations' => $course->description_translations,
            'college_translations' => $course->college_translations,
            'grade_year' => $course->grade_year,
            'thumbnail_url' => $course->thumbnail_url,
            'difficulty_level' => $course->difficulty_level,
            'language' => $course->language,
            'course_code' => $course->course_code,
            'primary_instructor_id' => $course->primary_instructor_id,
            'tags' => $course->tags,
            'status' => $course->status,
            'is_published' => $course->is_published,
            'duration_minutes' => $course->duration_minutes,
            'is_featured' => $course->is_featured,
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
        ];
    }
}
