<?php

declare(strict_types=1);

namespace App\Services\Devices;

use App\Enums\DeviceChangeRequestSource;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\UserDeviceStatus;
use App\Exceptions\DomainException;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Access\StudentAccessService;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Devices\Contracts\DeviceChangeServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeviceChangeService implements DeviceChangeServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentAccessService $studentAccessService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function create(User $student, string $newDeviceId, string $model, string $osVersion, ?string $reason = null): DeviceChangeRequest
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request device changes.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->centerScopeService->assertCenterId($student, $student->center_id);

        /** @var UserDevice|null $active */
        $active = $student->devices()
            ->active()
            ->notDeleted()
            ->first();

        if ($active === null) {
            $this->deny(ErrorCodes::NO_ACTIVE_DEVICE, 'Active device required to request a change.', 422);
        }

        $pending = DeviceChangeRequest::query()
            ->forUser($student)
            ->pending()
            ->notDeleted()
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
            'status' => DeviceChangeRequestStatus::Pending,
            'reason' => $reason,
        ]);

        $this->audit($student, AuditActions::DEVICE_CHANGE_REQUEST_CREATED, [
            'center_id' => $request->center_id,
            'old_device_id' => $active->device_id,
            'new_device_id' => $newDeviceId,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function approve(
        User $admin,
        DeviceChangeRequest $request,
        ?string $newDeviceId = null,
        ?string $model = null,
        ?string $osVersion = null
    ): DeviceChangeRequest {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== DeviceChangeRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be approved.', 409);
        }

        $resolvedDeviceId = $newDeviceId ?? $request->new_device_id;
        $resolvedModel = $model ?? $request->new_model;
        $resolvedOsVersion = $osVersion ?? $request->new_os_version;

        if ($resolvedDeviceId === '' || $resolvedDeviceId === $request->current_device_id) {
            $this->deny(ErrorCodes::INVALID_STATE, 'A new device identifier is required to approve this request.', 422);
        }

        if ($resolvedModel === '' || $resolvedOsVersion === '') {
            $this->deny(ErrorCodes::INVALID_STATE, 'Device model and OS version are required to approve this request.', 422);
        }

        return DB::transaction(function () use ($admin, $request, $resolvedDeviceId, $resolvedModel, $resolvedOsVersion): DeviceChangeRequest {
            /** @var UserDevice|null $current */
            $current = UserDevice::query()
                ->forUserId((int) $request->user_id)
                ->where('device_id', $request->current_device_id)
                ->notDeleted()
                ->first();

            if ($current !== null) {
                $current->update(['status' => UserDeviceStatus::Revoked]);
            }

            /** @var UserDevice $newDevice */
            $newDevice = UserDevice::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'device_id' => $resolvedDeviceId,
                ],
                [
                    'model' => $resolvedModel,
                    'os_version' => $resolvedOsVersion,
                    'status' => UserDeviceStatus::Active,
                    'approved_at' => Carbon::now(),
                    'last_used_at' => Carbon::now(),
                ]
            );

            UserDevice::where('user_id', $request->user_id)
                ->where('id', '!=', $newDevice->id)
                ->update(['status' => UserDeviceStatus::Revoked->value]);

            $request->status = DeviceChangeRequestStatus::Approved;
            $request->new_device_id = $resolvedDeviceId;
            $request->new_model = $resolvedModel;
            $request->new_os_version = $resolvedOsVersion;
            $request->decided_by = $admin->id;
            $request->decided_at = Carbon::now();
            $request->save();

            $this->audit($admin, AuditActions::DEVICE_CHANGE_REQUEST_APPROVED, [
                'request_id' => $request->id,
                'center_id' => $request->center_id,
                'old_device_id' => $request->current_device_id,
                'new_device_id' => $resolvedDeviceId,
            ], $request->id);

            return $request->fresh() ?? $request;
        });
    }

    public function reject(User $admin, DeviceChangeRequest $request, ?string $reason = null): DeviceChangeRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== DeviceChangeRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be rejected.', 409);
        }

        $request->status = DeviceChangeRequestStatus::Rejected;
        $request->decision_reason = $reason;
        $request->decided_by = $admin->id;
        $request->decided_at = Carbon::now();
        $request->save();

        $this->audit($admin, AuditActions::DEVICE_CHANGE_REQUEST_REJECTED, [
            'request_id' => $request->id,
            'center_id' => $request->center_id,
            'old_device_id' => $request->current_device_id,
            'new_device_id' => $request->new_device_id,
            'decision_reason' => $reason,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function createFromOtp(
        User $student,
        string $newDeviceId,
        string $model,
        string $osVersion,
        ?string $reason,
        Carbon $otpVerifiedAt
    ): DeviceChangeRequest {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request device changes.',
            ErrorCodes::UNAUTHORIZED,
            403
        );

        $pending = DeviceChangeRequest::query()
            ->forUser($student)
            ->pendingOrPreApproved()
            ->notDeleted()
            ->exists();

        if ($pending) {
            $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending device change request already exists.', 422);
        }

        /** @var UserDevice|null $active */
        $active = $student->devices()
            ->active()
            ->notDeleted()
            ->first();

        /** @var DeviceChangeRequest $request */
        $request = DeviceChangeRequest::create([
            'user_id' => $student->id,
            'center_id' => $student->center_id,
            'current_device_id' => $active?->device_id,
            'new_device_id' => $newDeviceId,
            'new_model' => $model,
            'new_os_version' => $osVersion,
            'status' => DeviceChangeRequestStatus::Pending,
            'request_source' => DeviceChangeRequestSource::Otp,
            'otp_verified_at' => $otpVerifiedAt,
            'reason' => $reason,
        ]);

        $this->audit($student, AuditActions::DEVICE_CHANGE_REQUEST_CREATED_VIA_OTP, [
            'center_id' => $request->center_id,
            'old_device_id' => $active?->device_id,
            'new_device_id' => $newDeviceId,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function createByAdmin(User $admin, User $student, ?string $reason = null): DeviceChangeRequest
    {
        $this->studentAccessService->assertStudent(
            $student,
            'Only students can request device changes.',
            ErrorCodes::UNAUTHORIZED,
            403
        );
        $this->centerScopeService->assertAdminSameCenter($admin, $student);

        $pending = DeviceChangeRequest::query()
            ->forUser($student)
            ->pendingOrPreApproved()
            ->notDeleted()
            ->exists();

        if ($pending) {
            $this->deny(ErrorCodes::PENDING_REQUEST_EXISTS, 'A pending device change request already exists for this student.', 422);
        }

        /** @var UserDevice|null $active */
        $active = $student->devices()
            ->active()
            ->notDeleted()
            ->first();

        /** @var DeviceChangeRequest $request */
        $request = DeviceChangeRequest::create([
            'user_id' => $student->id,
            'center_id' => $student->center_id,
            'current_device_id' => $active?->device_id,
            'new_device_id' => '',
            'new_model' => '',
            'new_os_version' => '',
            'status' => DeviceChangeRequestStatus::Pending,
            'request_source' => DeviceChangeRequestSource::Admin,
            'reason' => $reason,
        ]);

        $this->audit($admin, AuditActions::DEVICE_CHANGE_REQUEST_CREATED_BY_ADMIN, [
            'student_id' => $student->id,
            'center_id' => $request->center_id,
            'old_device_id' => $active?->device_id,
        ], $request->id);

        return $request->fresh() ?? $request;
    }

    public function preApprove(User $admin, DeviceChangeRequest $request, ?string $reason = null): DeviceChangeRequest
    {
        $this->assertAdminScope($admin, $request);

        if ($request->status !== DeviceChangeRequestStatus::Pending) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Only pending requests can be pre-approved.', 409);
        }

        return DB::transaction(function () use ($admin, $request, $reason): DeviceChangeRequest {
            /** @var UserDevice|null $current */
            $current = UserDevice::query()
                ->forUserId((int) $request->user_id)
                ->active()
                ->notDeleted()
                ->first();

            if ($current !== null) {
                $current->update(['status' => UserDeviceStatus::Revoked]);
            }

            $request->status = DeviceChangeRequestStatus::PreApproved;
            $request->decision_reason = $reason;
            $request->decided_by = $admin->id;
            $request->decided_at = Carbon::now();
            $request->save();

            $this->audit($admin, AuditActions::DEVICE_CHANGE_REQUEST_PRE_APPROVED, [
                'request_id' => $request->id,
                'center_id' => $request->center_id,
                'old_device_id' => $request->current_device_id,
            ], $request->id);

            return $request->fresh() ?? $request;
        });
    }

    public function completePreApproved(
        DeviceChangeRequest $request,
        string $deviceId,
        string $model,
        string $osVersion
    ): UserDevice {
        return DB::transaction(function () use ($request, $deviceId, $model, $osVersion): UserDevice {
            /** @var UserDevice $device */
            $device = UserDevice::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'device_id' => $deviceId,
                ],
                [
                    'model' => $model,
                    'os_version' => $osVersion,
                    'status' => UserDeviceStatus::Active,
                    'approved_at' => Carbon::now(),
                    'last_used_at' => Carbon::now(),
                ]
            );

            UserDevice::where('user_id', $request->user_id)
                ->where('id', '!=', $device->id)
                ->update(['status' => UserDeviceStatus::Revoked->value]);

            $request->status = DeviceChangeRequestStatus::Approved;
            $request->new_device_id = $deviceId;
            $request->new_model = $model;
            $request->new_os_version = $osVersion;
            $request->save();

            /** @var User $user */
            $user = User::find($request->user_id);

            $this->audit($user, AuditActions::DEVICE_CHANGE_REQUEST_COMPLETED_VIA_LOGIN, [
                'request_id' => $request->id,
                'center_id' => $request->center_id,
                'old_device_id' => $request->current_device_id,
                'new_device_id' => $deviceId,
            ], $request->id);

            return $device;
        });
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
        $this->auditLogService->logByType($actor, DeviceChangeRequest::class, $entityId, $action, $metadata);
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
