<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Sections;

use App\Models\Pivots\CourseVideo;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class SectionVideoResource extends JsonResource
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

        $thumbnail = $video->thumbnail_url;
        if ($thumbnail === null && is_array($video->thumbnail_urls)) {
            $thumbnail = $video->thumbnail_urls['default'] ?? array_values($video->thumbnail_urls)[0] ?? null;
        }

        return [
            'id' => $pivot?->id ?? $video->id,
            'video_id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration' => $video->duration_seconds,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail_url' => $thumbnail,
            'source_type' => $video->source_type,
            'source_provider' => $video->source_provider,
            'source_url' => $video->source_url,
            'source_id' => $video->source_id,
            'library_id' => $video->library_id,
            'is_free' => $pivot?->visible ?? false,
            'order' => $pivot?->order_index,
            'created_at' => $pivot?->created_at ?? $video->created_at,
            'updated_at' => $pivot?->updated_at ?? $video->updated_at,
        ];
    }
}
