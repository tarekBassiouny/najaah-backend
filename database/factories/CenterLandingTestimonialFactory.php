<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CenterLandingTestimonial>
 */
class CenterLandingTestimonialFactory extends Factory
{
    protected $model = CenterLandingTestimonial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_landing_page_id' => CenterLandingPage::factory(),
            'author_name' => $this->faker->name(),
            'author_title' => $this->faker->jobTitle(),
            'author_image_url' => $this->faker->imageUrl(200, 200),
            'content_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'شهادة رائعة عن المركز',
            ],
            'rating' => $this->faker->numberBetween(4, 5),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'sort_order' => $order,
        ]);
    }
}
