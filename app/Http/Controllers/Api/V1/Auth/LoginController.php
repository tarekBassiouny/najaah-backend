<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\APILoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Resources\Student\StudentUserResource;
use App\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private readonly APILoginAction $loginAction) {}

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

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        $centerId = is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
        $result = $this->loginAction->execute($data, $centerId);

        if ($result === null) {
            return response()->json(['error' => 'Invalid OTP'], 422);
        }

        return response()->json([
            'user' => new StudentUserResource($result['user']),
            'device' => new DeviceResource($result['device']),
            'tokens' => new TokenResource($result['tokens']),
        ]);
    }
}
