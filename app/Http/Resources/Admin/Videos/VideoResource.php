<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'title' => $video->translate('title'),
            'title_translations' => $video->title_translations,
            'description' => $video->translate('description'),
            'description_translations' => $video->description_translations,
            'tags' => $video->tags,
            'duration_seconds' => $video->duration_seconds,
            'source_type' => $video->source_type,
            'source_provider' => $video->source_provider,
            'encoding_status' => $video->encoding_status->value,
            'encoding_status_key' => Str::snake($video->encoding_status->name),
            'encoding_status_label' => $video->encoding_status->name,
            'lifecycle_status' => $video->lifecycle_status->value,
            'lifecycle_status_key' => Str::snake($video->lifecycle_status->name),
            'lifecycle_status_label' => $video->lifecycle_status->name,
            'upload_session_id' => $video->upload_session_id,
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $video->created_at,
        ];
    }
}
