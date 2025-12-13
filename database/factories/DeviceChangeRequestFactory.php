<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\DeviceChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceChangeRequestFactory extends Factory
{
    protected $model = DeviceChangeRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'center_id' => Center::factory(),
            'current_device_id' => 'old-device',
            'new_device_id' => 'new-device',
            'new_model' => 'Model X',
            'new_os_version' => '1.0',
            'status' => DeviceChangeRequest::STATUS_PENDING,
            'reason' => $this->faker->sentence(),
            'decision_reason' => null,
            'decided_by' => null,
            'decided_at' => null,
        ];
    }
}
