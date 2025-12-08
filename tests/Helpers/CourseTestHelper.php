<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pdf;
use App\Models\Pivots\CoursePdf;
use App\Models\Pivots\CourseVideo;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

trait CourseTestHelper
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createCourse(array $attributes = []): Course
    {
        /** @var Course $course */
        $course = Course::factory()->create($attributes);

        return $course;
    }

    public function createCourseWithSections(int $count = 2): Course
    {
        $course = $this->createCourse();

        Section::factory()
            ->count($count)
            ->create([
                'course_id' => $course->id,
            ]);

        /** @var Course $fresh */
        $fresh = $course->fresh(['sections']) ?? $course;

        return $fresh;
    }

    public function createCourseWithVideos(int $videoCount = 2): Course
    {
        $course = $this->createCourseWithSections();

        /** @var Collection<int, Video> $videos */
        $videos = Video::factory()->count($videoCount)->create();

        foreach ($videos as $index => $video) {
            CourseVideo::create([
                'course_id' => $course->id,
                'video_id' => $video->id,
                'order_index' => $index + 1,
                'visible' => true,
            ]);
        }

        /** @var Course $fresh */
        $fresh = $course->fresh(['videos', 'sections']) ?? $course;

        return $fresh;
    }

    public function createCourseWithPdfs(int $pdfCount = 2): Course
    {
        $course = $this->createCourseWithSections();

        /** @var Collection<int, Pdf> $pdfs */
        $pdfs = Pdf::factory()->count($pdfCount)->create();

        foreach ($pdfs as $index => $pdf) {
            CoursePdf::create([
                'course_id' => $course->id,
                'pdf_id' => $pdf->id,
                'order_index' => $index + 1,
                'visible' => true,
            ]);
        }

        /** @var Course $fresh */
        $fresh = $course->fresh(['pdfs', 'sections']) ?? $course;

        return $fresh;
    }

    public function publishCourse(Course $course): Course
    {
        $course->status = 3;
        $course->is_published = true;
        $course->publish_at = now();
        $course->save();

        /** @var Course $fresh */
        $fresh = $course->fresh() ?? $course;

        return $fresh;
    }

    public function attachInstructor(Course $course, ?Instructor $instructor = null): Course
    {
        $instructor = $instructor ?? Instructor::factory()->create([
            'center_id' => $course->center_id,
            'created_by' => $course->created_by,
        ]);

        $course->instructors()->syncWithoutDetaching([$instructor->id]);
        $course->primary_instructor_id = $course->primary_instructor_id ?? (int) $instructor->id;
        $course->save();

        /** @var Course $fresh */
        $fresh = $course->fresh(['instructors']) ?? $course;

        return $fresh;
    }
}
