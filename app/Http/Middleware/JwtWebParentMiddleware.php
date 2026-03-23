<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TokenPlatform;
use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Settings\PolicySettingsService;
use App\Support\ErrorCodes;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtWebParentMiddleware
{
    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        Auth::shouldUse('web-parent');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('web-parent');

        try {
            $user = $guard->user() ?? $guard->authenticate();
        } catch (\Throwable) {
            return $this->deny();
        }

        if (! $user instanceof User) {
            return $this->deny();
        }

        if (! $user->is_parent) {
            return $this->deny(ErrorCodes::UNAUTHORIZED, 'Only parents can access this endpoint.');
        }

        if ((int) $user->status !== UserStatus::Active->value) {
            return $this->deny(ErrorCodes::STUDENT_INACTIVE, 'Account is inactive.');
        }

        /*
        |--------------------------------------------------------------------------
        | Center validation
        |--------------------------------------------------------------------------
        */
        $resolvedCenterId = $this->resolveCenterId($request->attributes->get('resolved_center_id'));

        if ($resolvedCenterId === null) {
            if (is_numeric($user->center_id)) {
                return $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.');
            }
        } elseif (! is_numeric($user->center_id) || (int) $user->center_id !== $resolvedCenterId) {
            return $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.');
        } elseif (! $this->isActiveCenter($resolvedCenterId)) {
            return $this->deny(ErrorCodes::CENTER_MISMATCH, 'Center mismatch.');
        }

        /*
        |--------------------------------------------------------------------------
        | Parent portal setting check
        |--------------------------------------------------------------------------
        */
        if (is_numeric($user->center_id)) {
            $center = Center::find((int) $user->center_id);

            if ($center instanceof Center) {
                $policy = $this->policySettingsService->resolveCenterPolicy($center);
                if (! ($policy['allow_parent_portal'] ?? false)) {
                    return $this->deny(ErrorCodes::PARENT_PORTAL_DISABLED, 'Parent portal is not enabled for this center.');
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Token validation
        |--------------------------------------------------------------------------
        */
        $token = $guard->getToken();

        if ($token === null) {
            return $this->deny();
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::query()
            ->where('access_token', (string) $token)
            ->where('platform', TokenPlatform::Web)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return $this->deny();
        }

        /*
        |--------------------------------------------------------------------------
        | Device validation
        |--------------------------------------------------------------------------
        */
        $authenticatedDevice = null;

        if ($record->device_id !== null) {
            /** @var UserDevice|null $device */
            $device = UserDevice::find($record->device_id);

            if ($device !== null && $device->status === UserDevice::STATUS_ACTIVE) {
                $authenticatedDevice = $device;
            }
        }

        $request->setUserResolver(fn (): User => $user);
        $request->attributes->set('authenticated_device', $authenticatedDevice);

        return $next($request);
    }

    private function deny(string $code = 'UNAUTHORIZED', string $message = 'Authentication required.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    private function resolveCenterId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function isActiveCenter(int $centerId): bool
    {
        return Center::query()
            ->where('id', $centerId)
            ->where('status', Center::STATUS_ACTIVE->value)
            ->exists();
    }
}
