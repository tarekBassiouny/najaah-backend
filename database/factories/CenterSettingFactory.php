<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterSettingFactory extends Factory
{
    protected $model = CenterSetting::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'settings' => [
                'default_view_limit' => 2,
                'allow_extra_view_requests' => true,
                'pdf_download_permission' => false,
            ],
        ];
    }
}
