<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Pdf;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PdfFactory extends Factory
{
    protected $model = Pdf::class;

    public function definition(): array
    {
        return [
            'title_translations' => [
                'en' => 'PDF: '.$this->faker->sentence(),
                'ar' => 'ملف: '.$this->faker->sentence(),
            ],

            'description_translations' => [
                'en' => $this->faker->paragraph(),
                'ar' => 'وصف: '.$this->faker->sentence(),
            ],

            'source_type' => $this->faker->numberBetween(0, 1),
            'source_provider' => $this->faker->randomElement(['s3', 'spaces', 'gcs']),
            'source_id' => Str::uuid()->toString(),
            'source_url' => $this->faker->url(),
            'file_size_kb' => $this->faker->numberBetween(10, 50_000),
            'file_extension' => $this->faker->randomElement(['pdf', 'docx']),
            'created_by' => User::factory(),
        ];
    }
}
