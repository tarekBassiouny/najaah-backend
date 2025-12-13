<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\Contracts\JwtServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __construct(
        private readonly JwtServiceInterface $jwtService
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = request()->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $this->jwtService->revokeCurrent();

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');
        $guard->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'data' => null,
        ]);
    }
}
