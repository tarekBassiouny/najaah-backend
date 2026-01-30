<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Exceptions\DomainException;
use App\Models\AuditLog;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Logging\LogContextResolver;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceService implements DeviceServiceInterface
{
    public function __construct(
        private readonly DeviceChangeService $deviceChangeService
    ) {}

    /** @param array<string, mixed> $meta */
    public function register(User $user, string $uuid, array $meta): UserDevice
    {
        return DB::transaction(function () use ($user, $uuid, $meta): UserDevice {
            $model = $meta['device_type'] ?? $meta['device_name'] ?? 'Unknown';
            $osVersion = $meta['device_os'] ?? 'unknown';

            // Check for pre-approved device change request first
            $preApprovedDevice = $this->handlePreApprovedRequest($user, $uuid, $model, $osVersion);
            if ($preApprovedDevice !== null) {
                return $preApprovedDevice;
            }

            // Try reinstall detection first
            $reinstallDevice = $this->handleReinstall($user, $uuid, $model, $osVersion);
            if ($reinstallDevice !== null) {
                return $reinstallDevice;
            }

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
                    'model' => $model,
                    'os_version' => $osVersion,
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => now(),
                    'last_used_at' => now(),
                ]);
            } else {
                $device->update([
                    'model' => $model,
                    'os_version' => $osVersion,
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

    /**
     * Find an existing active device by fingerprint (model + OS version).
     */
    public function findByFingerprint(User $user, string $model, string $osVersion): ?UserDevice
    {
        /** @var UserDevice|null $device */
        $device = UserDevice::where('user_id', $user->id)
            ->where('model', $model)
            ->where('os_version', $osVersion)
            ->where('status', UserDevice::STATUS_ACTIVE)
            ->first();

        return $device;
    }

    /**
     * Handle potential app reinstall by matching device fingerprint.
     */
    public function handleReinstall(User $user, string $newDeviceId, string $model, string $osVersion): ?UserDevice
    {
        $existing = $this->findByFingerprint($user, $model, $osVersion);

        if ($existing !== null && $existing->device_id !== $newDeviceId) {
            $oldDeviceId = $existing->device_id;

            $existing->update([
                'device_id' => $newDeviceId,
                'os_version' => $osVersion,
                'last_used_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'device_uuid_updated',
                'entity_type' => UserDevice::class,
                'entity_id' => $existing->id,
                'metadata' => [
                    'old_device_id' => $oldDeviceId,
                    'new_device_id' => $newDeviceId,
                    'reason' => 'reinstall_detected',
                ],
            ]);

            Log::info('Device reinstall detected.', $this->resolveLogContext([
                'source' => 'api',
                'user_id' => $user->id,
                'center_id' => $user->center_id,
                'old_device_id' => $oldDeviceId,
                'new_device_id' => $newDeviceId,
            ]));

            return $existing;
        }

        return null;
    }

    /**
     * Handle pre-approved device change request during login.
     */
    public function handlePreApprovedRequest(User $user, string $deviceId, string $model, string $osVersion): ?UserDevice
    {
        /** @var DeviceChangeRequest|null $preApproved */
        $preApproved = DeviceChangeRequest::where('user_id', $user->id)
            ->where('status', DeviceChangeRequest::STATUS_PRE_APPROVED)
            ->whereNull('deleted_at')
            ->first();

        if ($preApproved === null) {
            return null;
        }

        Log::info('Pre-approved device change request found, completing.', $this->resolveLogContext([
            'source' => 'api',
            'user_id' => $user->id,
            'center_id' => $user->center_id,
            'request_id' => $preApproved->id,
            'new_device_id' => $deviceId,
        ]));

        return $this->deviceChangeService->completePreApproved($preApproved, $deviceId, $model, $osVersion);
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
