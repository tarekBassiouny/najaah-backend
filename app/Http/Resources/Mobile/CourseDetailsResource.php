<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\Concerns\ResolvesCourseRequiresVideoApproval;
use App\Models\Course;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Course
 */
class CourseDetailsResource extends JsonResource
{
    use ResolvesCourseRequiresVideoApproval;

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
            'thumbnail' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'requires_video_approval' => $this->resolveRequiresVideoApproval($course),
            'access_model' => $course->access_model->value,
            'is_enrolled' => $this->resolveHasAccess($course),
            'has_access' => $this->resolveHasAccess($course),
            'enrollment_status' => $course->enrollment_status ?? null,
            'published_at' => $course->publish_at,
            'duration_minutes' => $course->duration_minutes,
            'primary_instructor_id' => $course->primary_instructor_id,
            'center' => new CenterResource($this->whenLoaded('center')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'primary_instructor' => new InstructorResource($this->whenLoaded('primaryInstructor')),
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
            'sections' => CourseSectionResource::collection($this->whenLoaded('sections')),
            'videos' => CourseVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => CoursePdfResource::collection($this->whenLoaded('pdfs')),
        ];
    }

    /**
     * Resolve whether the student has access to the course.
     * This is the unified access flag that works for both access models:
     * - Enrollment model: has active enrollment
     * - Video code model: has at least one video code redemption
     *
     * Mobile can use this field (or is_enrolled) to show "enrolled/accessed" state.
     */
    private function resolveHasAccess(Course $course): bool
    {
        if ($course->usesEnrollmentAccess()) {
            return (bool) ($course->is_enrolled ?? false);
        }

        // For video_code model, check if student has any code redemptions
        return (bool) ($course->has_video_code_access ?? false);
    }
}
