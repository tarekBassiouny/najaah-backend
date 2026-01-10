<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PdfFactory extends Factory
{
    protected $model = Pdf::class;

    public function definition(): array
    {
        $center = Center::factory();

        return [
            'center_id' => $center,
            'title_translations' => [
                'en' => 'PDF title',
                'ar' => 'عنوان الملف',
            ],

            'description_translations' => [
                'en' => 'PDF description',
                'ar' => 'وصف الملف',
            ],

            'source_type' => 0,
            'source_provider' => 's3',
            'source_id' => Str::uuid()->toString(),
            'source_url' => 'https://example.com/file.pdf',
            'file_size_kb' => 1024,
            'file_extension' => 'pdf',
            'created_by' => User::factory()->for($center, 'center'),
        ];
    }
}
