<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\AdminAuthServiceInterface;
use Illuminate\Support\Facades\Hash;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('admin')->plainTextToken,
        ];
    }

    public function logout(?User $user): void
    {
        $token = $user?->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    public function me(?User $user): ?User
    {
        return $user;
    }
}
