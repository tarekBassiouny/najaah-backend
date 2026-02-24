<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Enrollment;
use App\Models\PlaybackSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

        if ($course === null) {
            return [
                'id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                'expires_at' => $enrollment->expires_at?->toISOString(),
                'status' => $enrollment->status->value,
                'status_label' => $enrollment->statusLabel(),
                'progress_percentage' => 0.0,
                'course' => null,
            ];
        }

        // Collect all videos from all sections
        $videos = $course->sections->flatMap(fn ($section) => $section->videos);
        $videoCount = $videos->count();

        // Build video resources with context and collect progress data
        $videoProgressData = [];
        $videoResources = $videos->map(function ($video) use ($course, &$videoProgressData): \App\Http\Resources\Admin\StudentCourseVideoResource {
            $resource = new StudentCourseVideoResource($video);
            if ($this->student !== null && $this->playbackSessions !== null) {
                $resource->setContext($this->student, $course, $this->playbackSessions);

                // Get progress for this video to calculate enrollment progress
                $videoSessions = $this->playbackSessions->where('video_id', $video->id);
                $latestSession = $videoSessions->sortByDesc('id')->first();
                $videoProgressData[] = $latestSession?->progress_percent ?? 0;
            }

            return $resource;
        });

        // Calculate enrollment progress as average of video progress
        $progressPercentage = $videoCount > 0 && ! empty($videoProgressData)
            ? round(array_sum($videoProgressData) / count($videoProgressData), 1)
            : 0.0;

        return [
            'id' => $enrollment->id,
            'enrolled_at' => $enrollment->enrolled_at->toISOString(),
            'expires_at' => $enrollment->expires_at?->toISOString(),
            'status' => $enrollment->status->value,
            'status_label' => $enrollment->statusLabel(),
            'progress_percentage' => $progressPercentage,
            'course' => [
                'id' => $course->id,
                'title' => $course->translate('title'),
                'title_translations' => $course->title_translations,
                'description' => $course->translate('description'),
                'description_translations' => $course->description_translations,
                'thumbnail' => $course->thumbnail_url,
                'thumbnail_url' => $course->thumbnail_url,
                'status' => $course->status->value,
                'status_key' => Str::snake($course->status->name),
                'status_label' => $course->status->name,
                'is_published' => (bool) $course->is_published,
                'video_count' => $videoCount,
                'videos' => $videoResources,
            ],
        ];
    }
}
