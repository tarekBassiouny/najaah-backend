<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use Illuminate\Support\Facades\Auth;

class AdminAuthService implements AdminAuthServiceInterface
{
    public function login(string $email, string $password): ?array
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'is_student' => false, // prevent student login
        ];

        $token = Auth::guard('admin')->attempt($credentials);

        if (! is_string($token) || $token === '') {
            return null;
        }

        /** @var User $user */
        $user = Auth::guard('admin')->user();

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
