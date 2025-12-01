<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Contracts\JwtServiceInterface;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtService implements JwtServiceInterface
{
    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function create(User $user, UserDevice $device): array
    {
        $access = JWTAuth::fromUser($user);
        $refresh = bin2hex(random_bytes(40));

        DB::transaction(function () use ($user, $device, $refresh): void {
            JwtToken::create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'access_token' => '',
                'refresh_token' => $refresh,
                'expires_at' => now()->addMinutes(30),
                'refresh_expires_at' => now()->addDays(30),
            ]);
        });

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function refresh(string $refreshToken): array
    {
        /** @var JwtToken|null $record */
        $record = JwtToken::where('refresh_token', $refreshToken)
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now())
            ->first();

        if ($record === null) {
            return [
                'access_token' => '',
                'refresh_token' => '',
            ];
        }

        /** @var User $user */
        $user = $record->user;

        $access = JWTAuth::fromUser($user);

        $record->update([
            'access_token' => $access,
            'expires_at' => now()->addMinutes(30),
        ]);

        return [
            'access_token' => $access,
            'refresh_token' => $refreshToken,
        ];
    }
}
