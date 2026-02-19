<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\ExtraViews;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\CourseSummaryResource;
use App\Http\Resources\Admin\Summary\StudentSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Http\Resources\Admin\Summary\VideoSummaryResource;
use App\Models\ExtraViewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin ExtraViewRequest
 */
class ExtraViewRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ExtraViewRequest $req */
        $req = $this->resource;

        return [
            'id' => $req->id,
            'user' => new StudentSummaryResource($this->whenLoaded('user')),
            'video' => new VideoSummaryResource($this->whenLoaded('video')),
            'course' => new CourseSummaryResource($this->whenLoaded('course')),
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'center_id' => $req->center_id,
            'status' => $req->status->value,
            'status_key' => Str::snake($req->status->name),
            'status_label' => $req->status->name,
            'reason' => $req->reason,
            'granted_views' => $req->granted_views,
            'decision_reason' => $req->decision_reason,
            'decider' => new UserSummaryResource($this->whenLoaded('decider')),
            'decided_at' => $req->decided_at,
            'requested_at' => $req->created_at,
            'created_at' => $req->created_at,
            'updated_at' => $req->updated_at,
        ];
    }
}
