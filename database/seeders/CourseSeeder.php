<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Center::all()->each(function (Center $center): void {
            Course::factory()
                ->count(10)
                ->create(['center_id' => $center->id])
                ->each(function (Course $course): void {
                    // For each course, attach settings
                    \App\Models\CourseSetting::factory()->create([
                        'course_id' => $course->id,
                    ]);
                });
        });
    }
}
