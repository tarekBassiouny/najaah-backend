<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin VideoUploadSession
 */
class VideoUploadSessionStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoUploadSession $session */
        $session = $this->resource;
        /** @var Video|null $video */
        $video = $session->videos->sortByDesc('id')->first();
        /** @var \DateTimeInterface|null $expiresAt */
        $expiresAt = $session->expires_at;

        return [
            'id' => $session->id,
            'video_id' => $video?->id,
            'upload_status' => $session->upload_status->value,
            'upload_status_key' => Str::snake($session->upload_status->name),
            'upload_status_label' => $session->upload_status->name,
            'progress_percent' => $session->progress_percent,
            'last_error_message' => $session->error_message,
            'expires_at' => $expiresAt?->format(DATE_ATOM),
            'created_at' => $session->created_at,
            'video' => $video === null ? null : [
                'id' => $video->id,
                'tags' => $video->tags,
                'duration_seconds' => $video->duration_seconds,
                'thumbnail_url' => $video->thumbnail_url,
                'encoding_status_key' => Str::snake($video->encoding_status->name),
                'lifecycle_status_key' => Str::snake($video->lifecycle_status->name),
                'updated_at' => $video->updated_at,
            ],
        ];
    }
}
