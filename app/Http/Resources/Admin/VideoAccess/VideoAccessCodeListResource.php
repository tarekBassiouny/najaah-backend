<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\StudentSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\VideoAccessCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin VideoAccessCode
 */
class VideoAccessCodeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var VideoAccessCode $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'code' => $resource->code,
            'status' => $resource->status->value,
            'status_key' => Str::snake($resource->status->name),
            'status_label' => $resource->status->name,
            'expires_at' => $resource->expires_at,
            'used_at' => $resource->used_at,
            'student' => new StudentSummaryResource($this->whenLoaded('user')),
            'video' => new VideoSummaryResource($this->whenLoaded('video')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'generated_at' => $resource->generated_at,
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }
}
