<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Videos;

use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class AdminVideoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;

        /** @var User|null $creator */
        $creator = $video->getRelationValue('creator');

        $uploadSession = $video->getRelationValue('uploadSession');

        return [
            'id' => $video->id,
            'center_id' => $video->center_id,
            'title' => $video->translate('title'),
            'description' => $video->translate('description'),
            'encoding_status' => $video->encoding_status,
            'lifecycle_status' => $video->lifecycle_status,
            'created_by' => $creator?->id,
            'created_at' => $video->created_at,
            'upload_sessions' => $uploadSession === null ? [] : [[
                'id' => $uploadSession->id,
                'upload_status' => $uploadSession->upload_status,
                'error_message' => $uploadSession->error_message,
                'created_at' => $uploadSession->created_at,
            ]],
        ];
    }
}
