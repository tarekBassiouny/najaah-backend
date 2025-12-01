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
            'model' => $this->faker->word(),
            'os_version' => $this->faker->randomElement(['iOS 17', 'Android 14', 'Windows 11', 'macOS 14']),
            'status' => 0,
            'approved_at' => now(),
            'last_used_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
