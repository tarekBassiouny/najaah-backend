<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        Course::all()->each(function (Course $course): void {
            Section::factory()
                ->count(2)
                ->create([
                    'course_id' => $course->id,
                ]);
        });
    }
}
