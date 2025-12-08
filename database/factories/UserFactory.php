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
            'name' => 'Test User',
            'username' => 'user'.uniqid(),
            'phone' => '1000000000',
            'country_code' => '+2',
            'email' => 'user'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
            'is_student' => false,
            'avatar_url' => null,
            'last_login_at' => now(),
        ];
    }
}
