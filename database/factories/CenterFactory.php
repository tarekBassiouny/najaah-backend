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
            'slug' => 'center-'.uniqid(),
            'type' => 0,
            'name_translations' => [
                'en' => 'Center Name',
                'ar' => 'مركز تجريبي',
            ],
            'description_translations' => [
                'en' => 'Description',
                'ar' => 'وصف المركز',
            ],
            'logo_url' => 'https://via.placeholder.com/200.png',
            'primary_color' => '#000000',
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
            'device_limit' => 1,
        ];
    }
}
