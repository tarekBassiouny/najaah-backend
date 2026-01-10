<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Exceptions\DomainException;
use App\Models\AuditLog;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Centers\CenterScopeService;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeviceChangeService
{
    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    public function create(User $student, string $newDeviceId, string $model, string $osVersion, ?string $reason = null): DeviceChangeRequest
    {
        $this->assertStudent($student);
        $this->centerScopeService->assertCenterId($student, $student->center_id);

        /** @var UserDevice|null $active */
        $active = $student->devices()
            ->where('status', UserDevice::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->first();

        if ($active === null) {
            $this->deny(ErrorCodes::NO_ACTIVE_DEVICE, 'Active device required to request a change.', 422);
        }

        $pending = DeviceChangeRequest::where('user_id', $student->id)
            ->where('status', DeviceChangeRequest::STATUS_PENDING)
            ->whereNull('deleted_at')
            ->exists();

        if ($pending) {
            $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending device change request already exists.', 422);
        }

        /** @var DeviceChangeRequest $request */
        $request = DeviceChangeRequest::create([
            'user_id' => $student->id,
            'center_id' => $student->center_id,
            'current_device_id' => $active->device_id,
            'new_device_id' => $newDeviceId,
            'new_model' => $model,
            'new_os_version' => $osVersion,
            'status' => DeviceChangeRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);

        $this->audit($student, 'device_change_request_created', [
            'old_device_id' => $active->device_id,
            'new_device_id' => $newDeviceId,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function approve(User $admin, DeviceChangeRequest $request): DeviceChangeRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== DeviceChangeRequest::STATUS_PENDING) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be approved.', 409);
        }

        return DB::transaction(function () use ($admin, $request): DeviceChangeRequest {
            /** @var UserDevice|null $current */
            $current = UserDevice::where('user_id', $request->user_id)
                ->where('device_id', $request->current_device_id)
                ->whereNull('deleted_at')
                ->first();

            if ($current !== null) {
                $current->update(['status' => UserDevice::STATUS_REVOKED]);
            }

            /** @var UserDevice $newDevice */
            $newDevice = UserDevice::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'device_id' => $request->new_device_id,
                ],
                [
                    'model' => $request->new_model,
                    'os_version' => $request->new_os_version,
                    'status' => UserDevice::STATUS_ACTIVE,
                    'approved_at' => Carbon::now(),
                    'last_used_at' => Carbon::now(),
                ]
            );

            UserDevice::where('user_id', $request->user_id)
                ->where('id', '!=', $newDevice->id)
                ->update(['status' => UserDevice::STATUS_REVOKED]);

            $request->status = DeviceChangeRequest::STATUS_APPROVED;
            $request->decided_by = $admin->id;
            $request->decided_at = Carbon::now();
            $request->save();

            $this->audit($admin, 'device_change_request_approved', [
                'request_id' => $request->id,
                'old_device_id' => $request->current_device_id,
                'new_device_id' => $request->new_device_id,
            ], $request->id);

            return $request->fresh() ?? $request;
        });
    }

    public function reject(User $admin, DeviceChangeRequest $request, ?string $reason = null): DeviceChangeRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== DeviceChangeRequest::STATUS_PENDING) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be rejected.', 409);
        }

        $request->status = DeviceChangeRequest::STATUS_REJECTED;
        $request->decision_reason = $reason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, 'device_change_request_rejected', [
            'request_id' => $request->id,
            'old_device_id' => $request->current_device_id,
            'new_device_id' => $request->new_device_id,
            'decision_reason' => $reason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    private function assertStudent(User $user): void
    {
        if (! $user->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only students can request device changes.', 403);
        }
    }

    private function assertAdminScope(User $admin, DeviceChangeRequest $request): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $request);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(User $actor, string $action, array $metadata, int $entityId): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => DeviceChangeRequest::class,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
