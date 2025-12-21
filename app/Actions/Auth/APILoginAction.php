<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Students\StudentService;
use Illuminate\Http\Exceptions\HttpResponseException;

class APILoginAction
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly JwtServiceInterface $jwtService,
        private readonly StudentService $studentService
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
    public function execute(array $data, ?int $centerId = null): ?array
    {
        $otp = $this->otpService->verify($data['otp'], $data['token']);

        if ($otp === null) {
            return null;
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
            $centerIdValue = (int) $centerId;
            if (is_numeric($user->center_id) && (int) $user->center_id !== $centerIdValue) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'CENTER_MISMATCH',
                        'message' => 'Center mismatch.',
                    ],
                ], 403));
            }

            if ($user->center_id === null) {
                $user->center_id = $centerIdValue;
                $user->save();
                $user->centers()->syncWithoutDetaching([
                    $centerIdValue => ['type' => 'student'],
                ]);
            }
        }

        $device = $this->deviceService->register($user, $data['device_uuid'], $data);
        $tokens = $this->jwtService->create($user, $device);

        return [
            'user' => $user,
            'device' => $device,
            'tokens' => $tokens,
        ];
    }
}
