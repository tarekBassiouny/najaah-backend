<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'name' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'phone' => $this->faker->unique()->numerify('1##########'),
            'country_code' => '+2',
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => $this->faker->numberBetween(0, 2),
            'is_student' => $this->faker->boolean(70),
            'avatar_url' => $this->faker->imageUrl(200, 200),
            'last_login_at' => $this->faker->dateTimeBetween('-20 days', 'now'),
        ];
    }
}
