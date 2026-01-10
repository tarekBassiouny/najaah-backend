<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\ExtraViews;

use App\Models\ExtraViewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'user_id' => $req->user_id,
            'center_id' => $req->center_id,
            'status' => $req->status,
            'created_at' => $req->created_at,
            'updated_at' => $req->updated_at,
        ];
    }
}
