<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class VideoResource extends JsonResource
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
            'center_id' => $video->center_id,
            'title' => $video->translate('title'),
            'description' => $video->translate('description'),
            'tags' => $video->tags,
            'duration_seconds' => $video->duration_seconds,
            'source_type' => $video->source_type,
            'source_provider' => $video->source_provider,
            'encoding_status' => $video->encoding_status,
            'lifecycle_status' => $video->lifecycle_status,
            'upload_session_id' => $video->upload_session_id,
            'created_by' => $video->created_by,
            'created_at' => $video->created_at,
        ];
    }
}
