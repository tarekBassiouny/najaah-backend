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
            'grade_id' => null,
            'school_id' => null,
            'college_id' => null,
            'name' => 'Test User',
            'username' => 'user'.uniqid(),
            'phone' => $this->faker->unique()->numerify('1#########'),
            'country_code' => '+2',
            'parent_phone' => null,
            'email' => 'user'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
            'is_student' => false,
            'is_parent' => false,
            'avatar_url' => null,
            'last_login_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if (! is_numeric($user->center_id)) {
                return;
            }

            $type = $user->is_student ? 'student' : 'admin';
            $user->centers()->syncWithoutDetaching([
                (int) $user->center_id => ['type' => $type],
            ]);
        });
    }

    public function student(): static
    {
        return $this->state(fn (): array => [
            'is_student' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'is_student' => false,
        ]);
    }

    public function parent(): static
    {
        return $this->state(fn (): array => [
            'is_parent' => true,
        ]);
    }
}
