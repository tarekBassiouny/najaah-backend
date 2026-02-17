<?php

declare(strict_types=1);

namespace App\Actions\Mobile;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Students\StudentService;
use App\Support\AuditActions;

class LoginAction
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly JwtServiceInterface $jwtService,
        private readonly StudentService $studentService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @param array{
     *   otp:string,
     *   token:string,
     *   device_uuid:string,
     *   device_name?:string,
     *   device_os?:string,
     *   device_type?:string
     * } $data
     * @return array{user:User,token:array{access_token:string,refresh_token:string}}|array{error:'INVALID_OTP'|'CENTER_MISMATCH'}
     */
    public function execute(array $data, ?int $centerId = null): array
    {
        $otp = $this->otpService->verify($data['otp'], $data['token']);

        if ($otp === null) {
            return ['error' => 'INVALID_OTP'];
        }

        $user = $otp->user;

        if (! $user instanceof User) {
            $user = $this->studentService->create([
                'name' => 'Student',
                'phone' => $otp->phone,
                'country_code' => $otp->country_code,
                'center_id' => $centerId,
            ]);

            $otp->user_id = $user->id;
            $otp->save();
        }

        if (is_numeric($centerId)) {
            $centerIdValue = $centerId;

            if (! is_numeric($user->center_id) || (int) $user->center_id !== $centerIdValue) {
                return ['error' => 'CENTER_MISMATCH'];
            }
        } elseif (is_numeric($user->center_id)) {
            return ['error' => 'CENTER_MISMATCH'];
        }

        $device = $this->deviceService->register(
            $user,
            $data['device_uuid'],
            $data
        );

        $token = $this->jwtService->create($user, $device);
        $user->last_login_at = now();
        $user->save();

        $this->auditLogService->log($user, $user, AuditActions::STUDENT_LOGIN);

        $user->load([
            'center',
            'roles',
            'devices' => fn ($q) => $q->where('status', UserDevice::STATUS_ACTIVE),
        ]);

        $user->setRelation('activeDevice', $user->devices->first());

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
