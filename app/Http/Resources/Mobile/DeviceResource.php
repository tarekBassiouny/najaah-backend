<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Enums\UserDeviceStatus;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $status = $device->status instanceof UserDeviceStatus
            ? $device->status
            : ($device->status !== null ? UserDeviceStatus::tryFrom((int) $device->status) : null);

        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'device_type' => $device->device_type,
            'model' => $device->model,
            'os_version' => $device->os_version,
            'status' => $status?->value ?? $device->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
            'approved_at' => $device->approved_at,
            'last_used_at' => $device->last_used_at,
        ];
    }
}
