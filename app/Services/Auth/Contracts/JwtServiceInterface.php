<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\User;
use App\Models\UserDevice;

interface JwtServiceInterface
{
    /**
     * @return array{
     *     access_token: string,
     *     refresh_token: string
     * }
     */
    public function create(User $user, UserDevice $device): array;

    /**
     * @return array{
     *     access_token: string,
     *     refresh_token: string
     * }
     */
    public function refresh(string $refreshToken): array;

    public function revokeCurrent(): void;
}
