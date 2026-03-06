<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Instructor;
use App\Services\Instructors\InstructorAvatarUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Instructor
 */
class InstructorWithCoursesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Instructor $instructor */
        $instructor = $this->resource;
        $avatarUrl = app(InstructorAvatarUrlResolver::class)->resolve($instructor->avatar_url);

        return [
            'id' => $instructor->id,
            'name' => $instructor->translate('name'),
            'title' => $instructor->translate('title'),
            'bio' => $instructor->translate('bio'),
            'avatar_url' => $avatarUrl,
            'courses' => ExploreCourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
