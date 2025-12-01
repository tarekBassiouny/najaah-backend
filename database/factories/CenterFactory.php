<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterFactory extends Factory
{
    protected $model = Center::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'type' => $this->faker->numberBetween(0, 1),
            'name_translations' => [
                'en' => $this->faker->company(),
                'ar' => 'مركز '.$this->faker->company(),
            ],
            'description_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],
            'logo_url' => $this->faker->imageUrl(200, 200),
            'primary_color' => $this->faker->hexColor(),
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
            'device_limit' => 1,
        ];
    }
}
