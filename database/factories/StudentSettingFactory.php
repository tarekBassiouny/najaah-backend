<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StudentSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentSettingFactory extends Factory
{
    protected $model = StudentSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'settings' => [
                'view_limit' => 2,
                'allow_extra_view_requests' => true,
                'pdf_download_permission' => false,
            ],
        ];
    }
}
