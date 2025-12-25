<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserDevice
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserDevice $device */
        $device = $this->resource;

        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'model' => $device->model,
            'os_version' => $device->os_version,
            'status' => $device->status,
            'approved_at' => $device->approved_at,
            'last_used_at' => $device->last_used_at,
        ];
    }
}
