<?php

declare(strict_types=1);

namespace App\Services\Devices\Contracts;

use App\Models\User;
use App\Models\UserDevice;

interface DeviceServiceInterface
{
    /**
     * Register or update a device for a user.
     * Handles pre-approved requests and reinstall detection.
     *
     * @param  array<string, mixed>  $meta  Device metadata (device_type/device_name, device_os)
     */
    public function register(User $user, string $uuid, array $meta): UserDevice;

    /**
     * Find an existing active device by fingerprint (model + OS version).
     */
    public function findByFingerprint(User $user, string $model, string $osVersion): ?UserDevice;

    /**
     * Handle potential app reinstall by matching device fingerprint.
     * Updates the device UUID if the same device (model + OS) is detected.
     */
    public function handleReinstall(User $user, string $newDeviceId, string $model, string $osVersion): ?UserDevice;

    /**
     * Handle pre-approved device change request during login.
     * Completes the request and activates the new device.
     */
    public function handlePreApprovedRequest(User $user, string $deviceId, string $model, string $osVersion): ?UserDevice;

    /**
     * Assert that the given device UUID matches the user's active device.
     *
     * @throws \App\Exceptions\DomainException If device doesn't match
     */
    public function assertActiveDevice(User $user, string $uuid): UserDevice;
}
