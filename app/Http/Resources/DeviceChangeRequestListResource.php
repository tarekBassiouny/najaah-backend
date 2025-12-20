<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeviceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceChangeRequest
 */
class DeviceChangeRequestListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DeviceChangeRequest $req */
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
