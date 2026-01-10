<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Actions\Mobile\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RefreshTokenRequest;
use App\Http\Requests\Mobile\SendOtpRequest;
use App\Http\Requests\Mobile\VerifyOtpRequest;
use App\Http\Resources\Mobile\StudentUserResource;
use App\Http\Resources\Mobile\TokenResource;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpServiceInterface $otpService,
        private readonly LoginAction $loginAction,
        private readonly JwtServiceInterface $jwtService
    ) {}

    public function send(SendOtpRequest $request): JsonResponse
    {
        /** @var array{phone:string,country_code:string} $data */
        $data = $request->validated();

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        $centerId = is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
        $otpResult = $this->otpService->send($data['phone'], $data['country_code'], $centerId);

        return response()->json([
            'success' => true,
            'token' => $otpResult,
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        /** @var array{otp:string,token:string,device_uuid:string,device_name?:string,device_os?:string,device_type?:string} $data */
        $data = $request->validated();

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        $centerId = is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
        $result = $this->loginAction->execute($data, $centerId);

        if (isset($result['error'])) {
            return $this->deny($result);
        }

        /** @var array{user:\App\Models\User,token:array{access_token:string,refresh_token:string}} $result */
        return response()->json([
            'success' => true,
            'data' => new StudentUserResource($result['user']),
            'token' => new TokenResource($result['token']),
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        /** @var array{refresh_token:string} $data */
        $data = $request->validated();

        $result = $this->jwtService->refresh($data['refresh_token']);

        return response()->json([
            'success' => true,
            'token' => new TokenResource($result),
        ]);
    }

    /**
     * @param  array{error:'INVALID_OTP'|'CENTER_MISMATCH'}  $result
     *
     * @phpstan-param array{error:'INVALID_OTP'|'CENTER_MISMATCH'} $result
     */
    private function deny(array $result): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $result['error'],
                'message' => match ($result['error']) {
                    'INVALID_OTP' => 'Invalid OTP.',
                    'CENTER_MISMATCH' => 'Center mismatch.',
                    default => 'Invalid request.',
                },
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
