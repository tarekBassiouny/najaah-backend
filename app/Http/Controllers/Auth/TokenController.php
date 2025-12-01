<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefreshTokenRequest;
use App\Services\Contracts\JwtServiceInterface;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    public function __construct(
        private readonly JwtServiceInterface $jwtService
    ) {
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        /** @var array{refresh_token:string} $data */
        $data = $request->validated();

        $result = $this->jwtService->refresh($data['refresh_token']);

        return response()->json($result);
    }
}
