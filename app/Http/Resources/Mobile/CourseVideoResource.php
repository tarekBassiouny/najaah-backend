<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Pivots\CourseVideo;
use App\Models\Video;
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

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'duration' => $video->duration_seconds,
            'thumbnail' => $video->thumbnail_url,
            'is_locked' => ! (bool) ($pivot?->visible ?? true),
        ];
    }
}
