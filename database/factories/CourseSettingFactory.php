<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseSettingFactory extends Factory
{
    protected $model = CourseSetting::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'settings' => [
                'view_limit' => 2,
                'allow_extra_view_requests' => true,
                'pdf_download_permission' => false,
            ],
        ];
    }
}
