<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\DeviceChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeviceChangeRequest
 */
class DeviceChangeLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DeviceChangeRequest $deviceChange */
        $deviceChange = $this->resource;

        return [
            'device_name' => $deviceChange->new_model,
            'device_id' => $deviceChange->new_device_id,
            'changed_at' => $deviceChange->decided_at?->toISOString(),
            'reason' => $deviceChange->reason ?? $deviceChange->decision_reason,
        ];
    }
}
