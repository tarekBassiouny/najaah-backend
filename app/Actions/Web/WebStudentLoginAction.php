<?php

declare(strict_types=1);

namespace App\Actions\Web;

use App\Enums\DeviceType;
use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\WebAuthServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Settings\PolicySettingsService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Str;

class WebStudentLoginAction
{
    public function __construct(
        private readonly WebAuthServiceInterface $webAuthService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly JwtServiceInterface $jwtService,
        private readonly AuditLogService $auditLogService,
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * @param array{
     *   otp: string,
     *   token: string,
     *   device_uuid?: string,
     *   device_name?: string,
     *   device_os?: string
     * } $data
     * @return array{user: User, token: array{access_token: string, refresh_token: string}, device_uuid: string}|array{error: string}
     */
    public function execute(array $data, ?int $centerId = null): array
    {
        $otp = $this->webAuthService->verifyOtp($data['otp'], $data['token']);

        if ($otp === null) {
            return ['error' => ErrorCodes::OTP_INVALID];
        }

        $user = $otp->user;

        if (! $user instanceof User) {
            // For web, student must already exist (no auto-creation like mobile)
            return ['error' => ErrorCodes::USER_NOT_FOUND_FOR_OTP];
        }

        if (! $user->is_student) {
            return ['error' => ErrorCodes::NOT_STUDENT];
        }

        // Center validation
        if (is_numeric($centerId)) {
            if (! is_numeric($user->center_id) || (int) $user->center_id !== $centerId) {
                return ['error' => ErrorCodes::CENTER_MISMATCH];
            }
        } elseif (is_numeric($user->center_id)) {
            return ['error' => ErrorCodes::CENTER_MISMATCH];
        }

        // Status validation
        if ((int) $user->status !== UserStatus::Active->value) {
            return [
                'error' => (int) $user->status === UserStatus::Banned->value
                    ? ErrorCodes::STUDENT_BANNED
                    : ErrorCodes::STUDENT_INACTIVE,
            ];
        }

        // Check web access setting
        $webDeviceLimit = 1;

        if (is_numeric($centerId)) {
            $center = Center::find($centerId);
            if ($center instanceof Center) {
                $policy = $this->policySettingsService->resolveCenterPolicy($center);
                if (! ($policy['allow_web_access'] ?? false)) {
                    return ['error' => ErrorCodes::WEB_ACCESS_DISABLED];
                }

                $webDeviceLimit = (int) ($policy['web_device_limit'] ?? 1);
            }
        }

        // Resolve or generate web device UUID
        $deviceUuid = $data['device_uuid'] ?? Str::uuid()->toString();

        $device = $this->deviceService->registerWeb(
            $user,
            $deviceUuid,
            [
                'device_name' => $data['device_name'] ?? null,
                'device_os' => $data['device_os'] ?? null,
            ],
            $webDeviceLimit
        );

        $token = $this->jwtService->create($user, $device, TokenPlatform::Web);

        $user->last_login_at = now();
        $user->save();

        $this->auditLogService->log($user, $user, AuditActions::STUDENT_LOGIN, [
            'platform' => 'web',
        ]);

        $user->load([
            'center',
            'roles',
            'devices' => fn ($q) => $q->where('status', UserDevice::STATUS_ACTIVE)->where('device_type', DeviceType::Web->value),
        ]);

        return [
            'user' => $user,
            'token' => $token,
            'device_uuid' => $deviceUuid,
        ];
    }
}
