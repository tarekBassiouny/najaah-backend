<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Devices;

use App\Http\Resources\Admin\Summary\CenterSummaryResource;
use App\Http\Resources\Admin\Summary\UserSummaryResource;
use App\Models\DeviceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'center' => new CenterSummaryResource($this->whenLoaded('center')),
            'center_id' => $req->center_id,
            'current_device_id' => $req->current_device_id,
            'new_device_id' => $req->new_device_id,
            'new_model' => $req->new_model,
            'new_os_version' => $req->new_os_version,
            'status' => $req->status->value,
            'status_key' => Str::snake($req->status->name),
            'status_label' => $req->status->name,
            'request_source' => $req->request_source->value,
            'request_source_key' => Str::snake($req->request_source->name),
            'request_source_label' => $req->request_source->name,
            'otp_verified_at' => $req->otp_verified_at,
            'reason' => $req->reason,
            'decision_reason' => $req->decision_reason,
            'decider' => new UserSummaryResource($this->whenLoaded('decider')),
            'decided_at' => $req->decided_at,
            'created_at' => $req->created_at,
        ];
    }
}
