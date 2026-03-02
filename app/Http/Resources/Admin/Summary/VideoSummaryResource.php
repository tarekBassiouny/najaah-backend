<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Summary;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight video representation for embedding in other resources.
 * MUST remain flat - no nested relations allowed.
 *
 * @mixin Video
 */
class VideoSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;

        $thumbnail = $video->thumbnail_url;
        if ($thumbnail === null && is_array($video->thumbnail_urls)) {
            $thumbnail = $video->thumbnail_urls['default'] ?? array_values($video->thumbnail_urls)[0] ?? null;
        }

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail_url' => $thumbnail,
            'source_type' => $video->source_type,
            'source_provider' => $video->source_provider,
            'source_url' => $video->source_url,
            'source_id' => $video->source_id,
            'library_id' => $video->library_id,
            'updated_at' => $video->updated_at,
        ];
    }
}
