<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\Student\StudentUserResource;
use App\Http\Resources\TokenResource;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\JwtServiceInterface;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly DeviceServiceInterface $deviceService,
        private readonly JwtServiceInterface $jwtService
    ) {}

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        /** @var array{
         *     otp:string,
         *     token:string,
         *     device_uuid:string,
         *     device_name?:string,
         *     device_os?:string,
         *     device_type?:string
         * } $data
         */
        $data = $request->validated();

        $otp = $this->otpService->verify(
            $data['otp'],
            $data['token']
        );

        if ($otp === null || $otp->user === null) {
            return response()->json(['error' => 'Invalid OTP'], 422);
        }

        $user = $otp->user;

        $device = $this->deviceService->register(
            $user,
            $data['device_uuid'],
            $data
        );

        $tokens = $this->jwtService->create($user, $device);

        return response()->json([
            'user' => new StudentUserResource($user),
            'device' => new DeviceResource($device),
            'tokens' => new TokenResource($tokens),
        ]);
    }
}
