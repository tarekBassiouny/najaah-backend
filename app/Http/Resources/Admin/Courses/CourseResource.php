<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Courses;

use App\Http\Resources\Admin\Sections\SectionResource;
use App\Http\Resources\Admin\Summary\CategorySummaryResource;
use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\InstructorSummaryResource;
use App\Http\Resources\Concerns\ResolvesCourseRequiresVideoApproval;
use App\Models\Course;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
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
            'title_translations' => $course->title_translations,
            'description' => $course->translate('description'),
            'description_translations' => $course->description_translations,
            'difficulty' => $course->difficulty_level ?? null,
            'language' => $course->language,
            'thumbnail' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
            'price' => $course->price ?? null,
            'status' => $course->status->value,
            'status_key' => Str::snake($course->status->name),
            'status_label' => $course->status->name,
            'is_published' => (bool) $course->is_published,
            'requires_video_approval' => $this->resolveRequiresVideoApproval($course),
            'show_for_all_students' => (bool) $course->show_for_all_students,
            'education_targets' => [
                'grades' => $this->mapEducationTargets($course->relationLoaded('grades') ? $course->grades : null),
                'schools' => $this->mapEducationTargets($course->relationLoaded('schools') ? $course->schools : null),
                'colleges' => $this->mapEducationTargets($course->relationLoaded('colleges') ? $course->colleges : null),
            ],
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'category' => new CategorySummaryResource($this->whenLoaded('category')),
            'primary_instructor' => new InstructorSummaryResource($this->whenLoaded('primaryInstructor')),
            'instructors' => InstructorSummaryResource::collection($this->whenLoaded('instructors')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'videos' => CourseVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => CoursePdfResource::collection($this->whenLoaded('pdfs')),
            'settings' => new CourseSettingResource($this->whenLoaded('setting')),
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
        ];
    }

    /**
     * @param  iterable<int, object>|null  $models
     * @return array<int, array{id:int,name:string}>
     */
    private function mapEducationTargets(?iterable $models): array
    {
        if ($models === null) {
            return [];
        }

        $mapped = [];

        foreach ($models as $model) {
            if (! $model instanceof Model || ! method_exists($model, 'translate')) {
                continue;
            }

            $mapped[] = [
                'id' => (int) $model->getAttribute('id'),
                'name' => (string) $model->translate('name'),
            ];
        }

        return $mapped;
    }
}
