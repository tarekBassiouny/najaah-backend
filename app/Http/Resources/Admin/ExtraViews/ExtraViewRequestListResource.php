<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\ExtraViews;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\ExtraViewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin ExtraViewRequest
 */
class ExtraViewRequestListResource extends JsonResource
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
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'center_id' => $req->center_id,
            'status' => $req->status->value,
            'status_key' => Str::snake($req->status->name),
            'status_label' => $req->status->name,
            'created_at' => $req->created_at,
            'updated_at' => $req->updated_at,
        ];
    }
}
