<?php

declare(strict_types=1);

namespace App\Services\Devices\Contracts;

use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Carbon;

interface DeviceChangeServiceInterface
{
    /**
     * Create a device change request initiated by a student.
     */
    public function create(
        User $student,
        string $newDeviceId,
        string $model,
        string $osVersion,
        ?string $reason = null
    ): DeviceChangeRequest;

    /**
     * Approve a pending device change request.
     * Revokes the current device and activates the new one.
     */
    public function approve(
        User $admin,
        DeviceChangeRequest $request,
        ?string $newDeviceId = null,
        ?string $model = null,
        ?string $osVersion = null
    ): DeviceChangeRequest;

    /**
     * Reject a pending device change request.
     */
    public function reject(
        User $admin,
        DeviceChangeRequest $request,
        ?string $reason = null
    ): DeviceChangeRequest;

    /**
     * Create a device change request via OTP verification.
     * Used when a student verifies ownership via phone OTP.
     */
    public function createFromOtp(
        User $student,
        string $newDeviceId,
        string $model,
        string $osVersion,
        ?string $reason,
        Carbon $otpVerifiedAt
    ): DeviceChangeRequest;

    /**
     * Create a device change request on behalf of a student by an admin.
     * The request will still need to be approved or pre-approved.
     */
    public function createByAdmin(
        User $admin,
        User $student,
        ?string $reason = null
    ): DeviceChangeRequest;

    /**
     * Pre-approve a pending device change request.
     * Revokes the current device but allows any new device on next login.
     */
    public function preApprove(
        User $admin,
        DeviceChangeRequest $request,
        ?string $reason = null
    ): DeviceChangeRequest;

    /**
     * Complete a pre-approved request when the student logs in with a new device.
     * Called automatically during device registration.
     */
    public function completePreApproved(
        DeviceChangeRequest $request,
        string $deviceId,
        string $model,
        string $osVersion
    ): UserDevice;
}
