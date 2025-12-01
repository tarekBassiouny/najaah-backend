<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Video;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    public function run(): void
    {
        Course::with('sections')->get()->each(function (Course $course): void {
            foreach ($course->sections as $section) {
                Video::factory()
                    ->count(5)
                    ->create()
                    ->each(function (Video $video) use ($course, $section): void {
                        // Attach via pivot with ordering/visibility
                        $course->videos()->attach($video->id, [
                            'section_id' => $section->id,
                            'order_index' => rand(1, 50),
                            'visible' => true,
                            'view_limit_override' => null,
                        ]);

                        \App\Models\VideoSetting::factory()->create([
                            'video_id' => $video->id,
                        ]);
                    });
            }
        });
    }
}
