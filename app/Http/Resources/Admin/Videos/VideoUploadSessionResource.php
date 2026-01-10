<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Models\Video;
use App\Models\VideoUploadSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'center_id' => $session->center_id,
            'uploaded_by' => $session->uploaded_by,
            'bunny_upload_id' => $session->bunny_upload_id,
            'upload_status' => $session->upload_status,
            'progress_percent' => $session->progress_percent,
            'error_message' => $session->error_message,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
            'videos' => $session->videos->map(static function (Video $video): array {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'encoding_status' => $video->encoding_status,
                    'lifecycle_status' => $video->lifecycle_status,
                    'source_id' => $video->source_id,
                    'source_url' => $video->source_url,
                    'original_filename' => $video->original_filename,
                ];
            }),
        ];
    }
}
