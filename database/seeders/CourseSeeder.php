<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Course;
use App\Models\CourseSetting;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Center::all()->each(function (Center $center): void {
            Course::factory()
                ->count(3)
                ->create(['center_id' => $center->id])
                ->each(function (Course $course): void {
                    // For each course, attach settings
                    CourseSetting::factory()->create([
                        'course_id' => $course->id,
                    ]);
                });
        });
    }
}
