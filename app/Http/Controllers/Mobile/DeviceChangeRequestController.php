<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreDeviceChangeRequest;
use App\Http\Requests\Mobile\SubmitDeviceChangeWithOtpRequest;
use App\Models\User;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Devices\DeviceChangeService;
use App\Services\Requests\RequestService;
use App\Support\ErrorCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DeviceChangeRequestController extends Controller
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly OtpServiceInterface $otpService,
        private readonly DeviceChangeService $deviceChangeService
    ) {}

    public function store(StoreDeviceChangeRequest $request): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $this->requestService->createDeviceChangeRequest($student, $request->input('reason'));

        return response()->json([
            'success' => true,
        ]);
    }

    public function submitWithOtp(SubmitDeviceChangeWithOtpRequest $request): JsonResponse
    {
        /** @var string $otp */
        $otp = $request->input('otp');
        /** @var string $token */
        $token = $request->input('token');

        $otpCode = $this->otpService->verify($otp, $token);

        if ($otpCode === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::OTP_INVALID,
                    'message' => 'Invalid or expired OTP.',
                ],
            ], 401);
        }

        $student = $otpCode->user;

        if ($student === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::USER_NOT_FOUND_FOR_OTP,
                    'message' => 'No user found for this phone number.',
                ],
            ], 404);
        }

        /** @var string $deviceUuid */
        $deviceUuid = $request->input('device_uuid');
        /** @var string $deviceModel */
        $deviceModel = $request->input('device_model');
        /** @var string $deviceOs */
        $deviceOs = $request->input('device_os');
        /** @var string|null $reason */
        $reason = $request->input('reason');

        $this->deviceChangeService->createFromOtp(
            $student,
            $deviceUuid,
            $deviceModel,
            $deviceOs,
            $reason,
            Carbon::now()
        );

        return response()->json([
            'success' => true,
            'message' => 'Device change request submitted successfully.',
        ]);
    }
}
