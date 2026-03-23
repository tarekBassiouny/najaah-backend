<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Web\WebStudentLoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RefreshTokenRequest;
use App\Http\Requests\Web\WebLoginRequest;
use App\Http\Requests\Web\WebOtpRequest;
use App\Http\Resources\Mobile\TokenResource;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\WebAuthServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentAuthController extends Controller
{
    public function __construct(
        private readonly WebAuthServiceInterface $webAuthService,
        private readonly WebStudentLoginAction $loginAction,
        private readonly JwtServiceInterface $jwtService
    ) {}

    public function sendOtp(WebOtpRequest $request): JsonResponse
    {
        /** @var array{phone: string, country_code: string} $data */
        $data = $request->validated();

        $centerId = $this->resolveCenterId($request);

        if ($centerId !== null && ! $this->isCenterActive($centerId)) {
            return $this->denyInactiveCenter();
        }

        $otpToken = $this->webAuthService->sendOtp($data['phone'], $data['country_code'], $centerId);

        return response()->json([
            'success' => true,
            'token' => $otpToken,
        ]);
    }

    public function verify(WebLoginRequest $request): JsonResponse
    {
        /** @var array{otp: string, token: string, device_uuid?: string, device_name?: string, device_os?: string} $data */
        $data = $request->validated();

        $centerId = $this->resolveCenterId($request);

        if ($centerId !== null && ! $this->isCenterActive($centerId)) {
            return $this->denyInactiveCenter();
        }

        $result = $this->loginAction->execute($data, $centerId);

        if (isset($result['error'])) {
            return $this->deny($result['error']);
        }

        /** @var array{user: User, token: array{access_token: string, refresh_token: string}, device_uuid: string} $result */
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'phone' => $result['user']->phone,
                'is_student' => $result['user']->is_student,
                'is_parent' => $result['user']->is_parent,
            ],
            'token' => new TokenResource($result['token']),
            'device_uuid' => $result['device_uuid'],
        ]);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        /** @var array{refresh_token: string} $data */
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

    public function logout(): JsonResponse
    {
        $this->jwtService->revokeCurrent();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'is_student' => $user->is_student,
                'is_parent' => $user->is_parent,
                'center_id' => $user->center_id,
            ],
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

    private function deny(string $errorCode): JsonResponse
    {
        $message = match ($errorCode) {
            ErrorCodes::OTP_INVALID => 'Invalid OTP.',
            ErrorCodes::CENTER_MISMATCH => 'Center mismatch.',
            ErrorCodes::STUDENT_INACTIVE => 'Student account is inactive.',
            ErrorCodes::STUDENT_BANNED => 'Student account is banned.',
            ErrorCodes::WEB_ACCESS_DISABLED => 'Web access is not enabled for this center.',
            ErrorCodes::USER_NOT_FOUND_FOR_OTP => 'No student account found for this phone number.',
            ErrorCodes::NOT_STUDENT => 'Only students can log in here.',
            ErrorCodes::WEB_DEVICE_LIMIT_REACHED => 'Web device limit reached.',
            default => 'Invalid request.',
        };

        $status = in_array($errorCode, [
            ErrorCodes::STUDENT_INACTIVE,
            ErrorCodes::STUDENT_BANNED,
            ErrorCodes::WEB_ACCESS_DISABLED,
            ErrorCodes::PARENT_PORTAL_DISABLED,
            ErrorCodes::WEB_DEVICE_LIMIT_REACHED,
        ], true) ? Response::HTTP_FORBIDDEN : Response::HTTP_UNPROCESSABLE_ENTITY;

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ], $status);
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
