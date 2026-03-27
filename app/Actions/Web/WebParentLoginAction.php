<?php

declare(strict_types=1);

namespace App\Actions\Web;

use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\WebAuthServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Settings\PolicySettingsService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WebParentLoginAction
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

        $user = $otp->user ?? $this->resolveUserFromOtp($otp, $centerId);

        if (! $user instanceof User) {
            return ['error' => ErrorCodes::USER_NOT_FOUND_FOR_OTP];
        }

        if (! $user->is_parent) {
            return ['error' => ErrorCodes::UNAUTHORIZED];
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
            return ['error' => ErrorCodes::STUDENT_INACTIVE];
        }

        // Check parent portal setting
        if (is_numeric($centerId)) {
            $center = Center::find($centerId);
            if ($center instanceof Center) {
                $policy = $this->policySettingsService->resolveCenterPolicy($center);
                if (! ($policy['allow_parent_portal'] ?? false)) {
                    return ['error' => ErrorCodes::PARENT_PORTAL_DISABLED];
                }
            }
        }

        // Parents use web devices but don't have strict device binding (no limit enforcement)
        $deviceUuid = $data['device_uuid'] ?? Str::uuid()->toString();

        $device = $this->deviceService->registerWeb(
            $user,
            $deviceUuid,
            [
                'device_name' => $data['device_name'] ?? null,
                'device_os' => $data['device_os'] ?? null,
            ],
            99 // Parents have no practical device limit
        );

        $token = $this->jwtService->create($user, $device, TokenPlatform::Web);

        $user->last_login_at = now();
        $user->save();

        $this->auditLogService->log($user, $user, AuditActions::STUDENT_LOGIN, [
            'platform' => 'web',
            'role' => 'parent',
        ]);

        $user->load(['center']);

        return [
            'user' => $user,
            'token' => $token,
            'device_uuid' => $deviceUuid,
        ];
    }

    private function resolveUserFromOtp(OtpCode $otp, ?int $centerId): ?User
    {
        $query = User::query()
            ->where('is_parent', true);

        if (is_numeric($centerId)) {
            $query->where('center_id', $centerId);
        } else {
            $query->whereNull('center_id');
        }

        $query->where(function (Builder $builder) use ($otp): void {
            if ($otp->phone_normalized !== null) {
                $builder->where('phone_normalized', $otp->phone_normalized)
                    ->orWhereRaw(
                        "CONCAT('+', REPLACE(REPLACE(COALESCE(country_code, ''), '+', ''), '00', ''), COALESCE(phone, '')) = ?",
                        [$otp->phone_normalized]
                    );

                return;
            }

            $builder->where('phone', $otp->phone)
                ->where('country_code', $otp->country_code);
        });

        return $query->first();
    }
}
