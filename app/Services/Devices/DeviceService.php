<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Logging\LogContextResolver;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceService implements DeviceServiceInterface
{
    /** @param array<string, mixed> $meta */
    public function register(User $user, string $uuid, array $meta): UserDevice
    {
        return DB::transaction(function () use ($user, $uuid, $meta): UserDevice {
            /** @var UserDevice|null $active */
            $active = $this->getActiveDevice($user);

            if ($active !== null && $active->device_id !== $uuid) {
                Log::warning('Device mismatch during registration.', $this->resolveLogContext([
                    'source' => 'api',
                    'user_id' => $user->id,
                    'center_id' => $user->center_id,
                ]));
                $this->deny(ErrorCodes::DEVICE_MISMATCH, 'A different device is already active for this user.');
            }

            /** @var UserDevice|null $device */
            $device = UserDevice::where('user_id', $user->id)
                ->where('device_id', $uuid)
                ->first();

            if ($device === null) {
                $device = UserDevice::create([
                    'user_id' => $user->id,
                    'device_id' => $uuid,
                    'model' => $meta['device_type'] ?? $meta['device_name'] ?? 'Unknown',
                    'os_version' => $meta['device_os'] ?? 'unknown',
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => now(),
                    'last_used_at' => now(),
                ]);
            } else {
                $device->update([
                    'model' => $meta['device_type'] ?? $meta['device_name'] ?? $device->model,
                    'os_version' => $meta['device_os'] ?? $device->os_version,
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => $device->approved_at ?? now(),
                    'last_used_at' => now(),
                ]);
            }

            UserDevice::where('user_id', $user->id)
                ->where('id', '!=', $device->id)
                ->update(['status' => UserDevice::STATUS_REVOKED]);

            return $device;
        });
    }

    public function assertActiveDevice(User $user, string $uuid): UserDevice
    {
        $active = $this->getActiveDevice($user);

        if ($active === null || $active->device_id !== $uuid) {
            Log::warning('Device mismatch during authorization.', $this->resolveLogContext([
                'source' => 'api',
                'user_id' => $user->id,
                'center_id' => $user->center_id,
            ]));
            $this->deny(ErrorCodes::DEVICE_MISMATCH, 'Device is not authorized for this user.');
        }

        return $active;
    }

    private function getActiveDevice(User $user): ?UserDevice
    {
        /** @var UserDevice|null $device */
        $device = UserDevice::where('user_id', $user->id)
            ->where('status', UserDevice::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();

        return $device;
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message): void
    {
        throw new DomainException($message, $code, 403);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return app(LogContextResolver::class)->resolve($overrides);
    }
}
