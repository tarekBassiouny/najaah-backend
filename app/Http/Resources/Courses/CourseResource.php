<?php

declare(strict_types=1);

namespace App\Http\Resources\Courses;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\CenterResource;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\Sections\SectionResource;
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
            'title' => $course->title,
            'description' => $course->description,
            'difficulty' => $course->difficulty_level ?? null,
            'language' => $course->language,
            'price' => $course->price ?? null,
            'status' => $course->status,
            'primary_instructor_id' => $course->primary_instructor_id,
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
            'center' => new CenterResource($this->whenLoaded('center')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'videos' => CourseVideoResource::collection($this->whenLoaded('videos')),
            'pdfs' => CoursePdfResource::collection($this->whenLoaded('pdfs')),
            'settings' => new CourseSettingResource($this->whenLoaded('setting')),
        ];
    }
}
