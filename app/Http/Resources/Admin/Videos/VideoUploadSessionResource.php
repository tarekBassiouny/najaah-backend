<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\VideoUploadSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin VideoUploadSession
 */
class VideoUploadSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoUploadSession $session */
        $session = $this->resource;

        return [
            'id' => $session->id,
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'uploader' => new UserSummaryResource($this->whenLoaded('uploader')),
            'bunny_upload_id' => $session->bunny_upload_id,
            'upload_status' => $session->upload_status->value,
            'upload_status_key' => Str::snake($session->upload_status->name),
            'upload_status_label' => $session->upload_status->name,
            'progress_percent' => $session->progress_percent,
            'error_message' => $session->error_message,
            'videos' => VideoSummaryResource::collection($this->whenLoaded('videos')),
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];
    }
}
