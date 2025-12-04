<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\APILoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyOtpRequest;
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

        $result = $this->loginAction->execute($data);

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
