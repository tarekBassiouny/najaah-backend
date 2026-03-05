<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\StudentSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\VideoAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VideoAccess
 */
class VideoAccessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoAccess $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'student' => new StudentSummaryResource($this->whenLoaded('user')),
            'video' => new VideoSummaryResource($this->whenLoaded('video')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'granted_at' => $resource->granted_at,
            'revoked_at' => $resource->revoked_at,
            'revoked_by' => new UserSummaryResource($this->whenLoaded('revoker')),
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }
}
