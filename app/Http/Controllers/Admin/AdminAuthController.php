<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Services\Contracts\AdminAuthServiceInterface;
use Illuminate\Http\JsonResponse;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthServiceInterface $authService) {}

    public function login(AdminLoginRequest $request): JsonResponse
    {
        /** @var array{email:string,password:string} $data */
        $data = $request->validated();

        $result = $this->authService->login($data['email'], $data['password']);

        if ($result === null) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'user' => new AdminUserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(request()->user());

        return response()->json(['message' => 'Logged out']);
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->me(request()->user());

        return response()->json([
            'user' => $user !== null ? new AdminUserResource($user) : null,
        ]);
    }
}
