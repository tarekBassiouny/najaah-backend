<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CenterFactory extends Factory
{
    protected $model = Center::class;

    public function definition(): array
    {
        return [
            'slug' => 'center-'.uniqid(),
            'api_key' => 'ctr_'.Str::random(32),
            'type' => 0,
            'tier' => Center::TIER_STANDARD,
            'is_featured' => false,
            'status' => Center::STATUS_ACTIVE,
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
            'onboarding_status' => Center::ONBOARDING_DRAFT,
            'branding_metadata' => null,
            'storage_driver' => 'spaces',
            'storage_root' => null,
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
            'device_limit' => 1,
            'allow_guest_browsing' => false,
        ];
    }
}
