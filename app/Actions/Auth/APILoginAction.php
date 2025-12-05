<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\JwtServiceInterface;
use App\Services\Contracts\OtpServiceInterface;

class APILoginAction
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly JwtServiceInterface $jwtService
    ) {}

    /**
     * @param array{
     *     otp: string,
     *     token: string,
     *     device_uuid: string,
     *     device_name?: string,
     *     device_os?: string,
     *     device_type?: string
     * } $data
     * @return array{user: User, device: UserDevice, tokens: array<string, mixed>}|null
     */
    public function execute(array $data): ?array
    {
        $otp = $this->otpService->verify($data['otp'], $data['token']);

        if ($otp === null || $otp->user === null) {
            return null;
        }

        $user = $otp->user;
        $device = $this->deviceService->register($user, $data['device_uuid'], $data);
        $tokens = $this->jwtService->create($user, $device);

        return [
            'user' => $user,
            'device' => $device,
            'tokens' => $tokens,
        ];
    }
}
