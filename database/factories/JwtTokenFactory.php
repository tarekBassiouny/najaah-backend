<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JwtToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class JwtTokenFactory extends Factory
{
    protected $model = JwtToken::class;

    public function definition(): array
    {
        $expires = now()->addHours(4);
        $refreshExpires = now()->addDays(30);

        return [
            'user_id' => User::factory(),
            'device_id' => UserDevice::factory(),
            'access_token' => Str::random(160),
            'refresh_token' => Str::random(200),
            'expires_at' => $expires,
            'refresh_expires_at' => $refreshExpires,
            'revoked_at' => null,
        ];
    }
}
