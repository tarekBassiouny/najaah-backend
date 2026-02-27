<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\Video;
use App\Models\VideoUploadSession;
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
            'thumbnail_url' => $video->thumbnail_url,
            'source_type' => $video->source_type,
            'source_provider' => $video->source_provider,
            'source_url' => $video->source_url,
            'encoding_status' => $video->encoding_status->value,
            'encoding_status_key' => Str::snake($video->encoding_status->name),
            'encoding_status_label' => $video->encoding_status->name,
            'lifecycle_status' => $video->lifecycle_status->value,
            'lifecycle_status_key' => Str::snake($video->lifecycle_status->name),
            'lifecycle_status_label' => $video->lifecycle_status->name,
            'upload_session_id' => $video->upload_session_id,
            'upload_sessions' => $this->whenLoaded('uploadSession', function () use ($video): array {
                /** @var VideoUploadSession|null $uploadSession */
                $uploadSession = $video->uploadSession;

                return $uploadSession === null ? [] : [[
                    'id' => $uploadSession->id,
                    'upload_status' => $uploadSession->upload_status->value,
                    'upload_status_key' => Str::snake($uploadSession->upload_status->name),
                    'upload_status_label' => $uploadSession->upload_status->name,
                    'error_message' => $uploadSession->error_message,
                    'created_at' => $uploadSession->created_at,
                    'updated_at' => $uploadSession->updated_at,
                ]];
            }, []),
            'creator' => new UserSummaryResource($this->whenLoaded('creator')),
            'created_at' => $video->created_at,
            'updated_at' => $video->updated_at,
        ];
    }
}
