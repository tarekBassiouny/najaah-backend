<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Pivots\CourseVideo;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class CourseVideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;
        /** @var CourseVideo|null $pivot */
        $pivot = $video->pivot instanceof CourseVideo ? $video->pivot : null;

        $isLocked = ! (bool) ($pivot?->visible ?? true);

        if (! $isLocked && $pivot !== null) {
            $isLocked = $this->isViewLimitExceeded($request, $video, $pivot);
        }

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration' => $video->duration_seconds,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail' => $video->thumbnail_url,
            'thumbnail_url' => $video->thumbnail_url,
            'is_locked' => $isLocked,
            'updated_at' => $video->updated_at,
        ];
    }

    private function isViewLimitExceeded(Request $request, Video $video, CourseVideo $pivot): bool
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        $course = $pivot->course;

        if ($course === null) {
            return false;
        }

        /** @var ViewLimitServiceInterface $viewLimitService */
        $viewLimitService = app(ViewLimitServiceInterface::class);

        return $viewLimitService->isLocked($user, $video, $course);
    }
}
