<?php

declare(strict_types=1);

namespace App\Services\Devices\Contracts;

use App\Models\User;
use App\Models\UserDevice;

interface DeviceServiceInterface
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function register(User $user, string $uuid, array $meta): UserDevice;
}
