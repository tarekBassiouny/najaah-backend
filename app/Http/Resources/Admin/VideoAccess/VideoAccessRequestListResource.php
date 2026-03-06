<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\StudentSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\VideoAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin VideoAccessRequest
 */
class VideoAccessRequestListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoAccessRequest $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'user' => new StudentSummaryResource($this->whenLoaded('user')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'video' => new VideoSummaryResource($this->whenLoaded('video')),
            'status' => $resource->status->value,
            'status_key' => Str::snake($resource->status->name),
            'status_label' => $resource->status->name,
            'reason' => $resource->reason,
            'decision_reason' => $resource->decision_reason,
            'decided_at' => $resource->decided_at,
            'requested_at' => $resource->created_at,
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }
}
