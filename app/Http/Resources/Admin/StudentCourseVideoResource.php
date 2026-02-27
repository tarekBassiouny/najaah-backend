<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Course;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Video
 */
class StudentCourseVideoResource extends JsonResource
{
    private ?User $student = null;

    private ?Course $course = null;

    /** @var Collection<int, PlaybackSession>|null */
    private ?Collection $playbackSessions = null;

    /**
     * @param  Collection<int, PlaybackSession>  $playbackSessions
     */
    public function setContext(User $student, Course $course, Collection $playbackSessions): self
    {
        $this->student = $student;
        $this->course = $course;
        $this->playbackSessions = $playbackSessions;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;

        // Filter playback sessions for this video
        $videoSessions = $this->playbackSessions?->where('video_id', $video->id) ?? collect();

        // Count full plays (watch_count)
        $watchCount = $videoSessions->where('is_full_play', true)->count();

        // Get latest progress
        $latestSession = $videoSessions->sortByDesc('id')->first();
        $progressPercent = $latestSession?->progress_percent ?? 0;

        // Get effective watch limit using the service
        $watchLimit = null;
        if ($this->student !== null && $this->course !== null) {
            /** @var ViewLimitServiceInterface $viewLimitService */
            $viewLimitService = app(ViewLimitServiceInterface::class);
            $watchLimit = $viewLimitService->getEffectiveLimit($this->student, $video, $this->course);
        }

        return [
            'id' => $video->id,
            'title' => $video->title,
            'tags' => $video->tags,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail_url' => $video->thumbnail_url,
            'watch_count' => $watchCount,
            'watch_limit' => $watchLimit,
            'watch_progress_percentage' => (float) $progressPercent,
            'updated_at' => $video->updated_at,
        ];
    }
}
