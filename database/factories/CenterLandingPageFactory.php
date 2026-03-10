<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LandingPageStatus;
use App\Models\Center;
use App\Models\CenterLandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CenterLandingPage>
 */
class CenterLandingPageFactory extends Factory
{
    protected $model = CenterLandingPage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'status' => LandingPageStatus::Draft,
            'meta_title' => $this->faker->sentence(4),
            'meta_description' => $this->faker->paragraph(),
            'meta_keywords' => implode(',', $this->faker->words(5)),
            'hero_title_translations' => [
                'en' => $this->faker->sentence(3),
                'ar' => 'عنوان البطل',
            ],
            'hero_subtitle_translations' => [
                'en' => $this->faker->sentence(6),
                'ar' => 'عنوان فرعي',
            ],
            'hero_background_url' => $this->faker->imageUrl(1920, 1080),
            'hero_cta_text' => 'Get Started',
            'hero_cta_url' => '/register',
            'about_title_translations' => [
                'en' => 'About Us',
                'ar' => 'معلومات عنا',
            ],
            'about_content_translations' => [
                'en' => $this->faker->paragraphs(2, true),
                'ar' => 'محتوى عن المركز',
            ],
            'about_image_url' => $this->faker->imageUrl(800, 600),
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_address' => $this->faker->address(),
            'social_facebook' => null,
            'social_twitter' => null,
            'social_instagram' => null,
            'social_youtube' => null,
            'social_linkedin' => null,
            'social_tiktok' => null,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'font_family' => 'Inter',
            'show_hero' => true,
            'show_about' => true,
            'show_courses' => true,
            'show_testimonials' => true,
            'show_contact' => true,
            'section_order' => CenterLandingPage::DEFAULT_SECTION_ORDER,
            'section_layouts' => CenterLandingPage::defaultSectionLayouts(),
            'section_styles' => CenterLandingPage::defaultSectionStyles(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LandingPageStatus::Published,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LandingPageStatus::Draft,
        ]);
    }
}
