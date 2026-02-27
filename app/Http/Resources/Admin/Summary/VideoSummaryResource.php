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

        return [
            'id' => $video->id,
            'title' => $video->translate('title'),
            'tags' => $video->tags,
            'duration_seconds' => $video->duration_seconds,
            'thumbnail_url' => $video->thumbnail_url,
            'updated_at' => $video->updated_at,
        ];
    }
}
