<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeviceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceChangeRequest
 */
class DeviceChangeRequestResource extends JsonResource
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
            'current_device_id' => $req->current_device_id,
            'new_device_id' => $req->new_device_id,
            'new_model' => $req->new_model,
            'new_os_version' => $req->new_os_version,
            'status' => $req->status,
            'reason' => $req->reason,
            'decision_reason' => $req->decision_reason,
            'decided_by' => $req->decided_by,
            'decided_at' => $req->decided_at,
            'created_at' => $req->created_at,
        ];
    }
}
