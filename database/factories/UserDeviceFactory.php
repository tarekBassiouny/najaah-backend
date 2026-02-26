<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => (string) Str::uuid(),
            'device_name' => 'device-name',
            'device_type' => 'device-type',
            'model' => 'device-model',
            'os_version' => 'os-version',
            'status' => UserDevice::STATUS_ACTIVE,
            'approved_at' => now(),
            'last_used_at' => now(),
        ];
    }
}
