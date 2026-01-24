<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Pdf;
use Illuminate\Database\Seeder;

class PdfSeeder extends Seeder
{
    public function run(): void
    {
        Course::with('sections', 'videos')->get()->each(function (Course $course): void {
            foreach ($course->sections as $section) {
                Pdf::factory()
                    ->count(2)
                    ->create()
                    ->each(function (Pdf $pdf) use ($course, $section): void {
                        $videoId = optional($course->videos->random())->id;

                        $course->pdfs()->attach($pdf->id, [
                            'section_id' => $section->id,
                            'video_id' => $videoId,
                            'order_index' => rand(1, 50),
                            'visible' => true,
                        ]);
                    });
            }
        });
    }
}
