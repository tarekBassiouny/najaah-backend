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
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $centerId = $this->resolveCenterId($request);

        if ($centerId !== null && ! $this->isCenterActive($centerId)) {
            return $this->denyInactiveCenter();
        }

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

        $centerId = $this->resolveCenterId($request);

        if ($centerId !== null && ! $this->isCenterActive($centerId)) {
            return $this->denyInactiveCenter();
        }

        $result = $this->loginAction->execute($data, $centerId);

        if (isset($result['error'])) {
            return $this->deny($result);
        }

        /** @var array{user:User,token:array{access_token:string,refresh_token:string}} $result */
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
        $centerId = $this->resolveCenterId($request);

        if (! $this->canRefreshForScope($data['refresh_token'], $centerId)) {
            return $this->emptyTokenResponse();
        }

        $result = $this->jwtService->refresh($data['refresh_token']);

        return response()->json([
            'success' => true,
            'token' => new TokenResource($result),
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }

    private function isCenterActive(int $centerId): bool
    {
        return Center::query()
            ->whereKey($centerId)
            ->where('status', Center::STATUS_ACTIVE->value)
            ->exists();
    }

    private function canRefreshForScope(string $refreshToken, ?int $resolvedCenterId): bool
    {
        $record = JwtToken::query()
            ->with(['user:id,center_id'])
            ->where('refresh_token', $refreshToken)
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now())
            ->first();

        if (! $record instanceof JwtToken) {
            return true;
        }

        $user = $record->user;
        if (! $user instanceof User) {
            return false;
        }

        if ($resolvedCenterId === null) {
            return ! is_numeric($user->center_id);
        }

        if (! is_numeric($user->center_id) || (int) $user->center_id !== $resolvedCenterId) {
            return false;
        }

        return $this->isCenterActive($resolvedCenterId);
    }

    private function emptyTokenResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'token' => new TokenResource([
                'access_token' => '',
                'refresh_token' => '',
            ]),
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

    private function denyInactiveCenter(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'CENTER_INACTIVE',
                'message' => 'Center is not active.',
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
