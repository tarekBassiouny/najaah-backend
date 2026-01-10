<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\ExtraViews;

use App\Models\ExtraViewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'user_id' => $req->user_id,
            'video_id' => $req->video_id,
            'course_id' => $req->course_id,
            'center_id' => $req->center_id,
            'status' => $req->status,
            'reason' => $req->reason,
            'granted_views' => $req->granted_views,
            'decision_reason' => $req->decision_reason,
            'decided_by' => $req->decided_by,
            'decided_at' => $req->decided_at,
            'created_at' => $req->created_at,
        ];
    }
}
