<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JwtService implements JwtServiceInterface
{
    public function create(User $user, UserDevice $device): array
    {
        $access = JWTAuth::fromUser($user);
        $refresh = bin2hex(random_bytes(10));

        DB::transaction(function () use ($user, $device, $refresh, $access): void {
            JwtToken::create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'access_token' => $access,
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

        /** @var User&JWTSubject $user */
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

    public function revokeCurrent(): void
    {
        $token = JWTAuth::getToken();
        if ($token === null) {
            return;
        }

        /** @var JwtToken|null $record */
        $record = JwtToken::where('access_token', (string) $token)->first();

        if ($record !== null) {
            $record->update(['revoked_at' => now()]);
        }

        JWTAuth::invalidate(true);
    }
}
