<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
            'entity_type' => User::class,
            'entity_id' => 1,
            'metadata' => [
                'ip' => $this->faker->ipv4(),
                'ua' => $this->faker->userAgent(),
            ],
        ];
    }
}
