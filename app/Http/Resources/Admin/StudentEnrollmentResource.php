<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Enrollment
 */
class StudentEnrollmentResource extends JsonResource
{
    private ?User $student = null;

    /** @var Collection<int, PlaybackSession>|null */
    private ?Collection $playbackSessions = null;

    /**
     * @param  Collection<int, PlaybackSession>  $playbackSessions
     */
    public function setContext(User $student, Collection $playbackSessions): self
    {
        $this->student = $student;
        $this->playbackSessions = $playbackSessions;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->resource;
        $course = $enrollment->course;

        // Collect all videos from all sections
        $videos = $course->sections->flatMap(fn ($section) => $section->videos);

        // Build video resources with context
        $videoResources = $videos->map(function ($video) use ($course): \App\Http\Resources\Admin\StudentCourseVideoResource {
            $resource = new StudentCourseVideoResource($video);
            if ($this->student !== null && $this->playbackSessions !== null) {
                $resource->setContext($this->student, $course, $this->playbackSessions);
            }

            return $resource;
        });

        return [
            'id' => $enrollment->id,
            'enrolled_at' => $enrollment->enrolled_at->toISOString(),
            'expires_at' => $enrollment->expires_at?->toISOString(),
            'status' => $enrollment->status->value,
            'status_label' => $enrollment->statusLabel(),
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'thumbnail_url' => $course->thumbnail_url,
                'videos' => $videoResources,
            ],
        ];
    }
}
