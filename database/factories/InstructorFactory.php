<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        $center = Center::factory();

        return [
            'center_id' => $center,
            'name_translations' => [
                'en' => $this->faker->name(),
                'ar' => 'د.'.$this->faker->lastName(),
            ],
            'bio_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'نبذة: '.$this->faker->sentence(),
            ],
            'title_translations' => [
                'en' => $this->faker->randomElement(['Professor', 'Dr', 'Trainer']),
                'ar' => $this->faker->randomElement(['أستاذ', 'دكتور', 'مدرب']),
            ],
            'avatar_url' => $this->faker->imageUrl(300, 300, 'people'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'social_links' => [
                'facebook' => $this->faker->url(),
                'instagram' => $this->faker->url(),
            ],
            'created_by' => User::factory()->for($center, 'center'),
        ];
    }
}
