<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Contracts\DeviceServiceInterface;

class DeviceService implements DeviceServiceInterface
{
    /** @param array<string, mixed> $meta */
    public function register(User $user, string $uuid, array $meta): UserDevice
    {
        /** @var UserDevice|null $device */
        $device = UserDevice::where('device_id', $uuid)->first();

        if ($device === null) {
            $device = UserDevice::create([
                'user_id' => $user->id,
                'device_id' => $uuid,
                'model' => $meta['device_name'] ?? 'Unknown',
                'os_version' => $meta['device_os'] ?? 'unknown',
                'status' => 0, // active
                'approved_at' => null,
            ]);
        } else {
            $device->update([
                'model' => $meta['device_name'] ?? $device->model,
                'os_version' => $meta['device_os'] ?? $device->os_version,
                'status' => 0, // active
            ]);
        }

        return $device;
    }
}
