<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        Center::all()->each(function (Center $center): void {
            $creator = User::where('center_id', $center->id)
                ->where('is_student', false)
                ->first()
                ?? User::factory()->create([
                    'center_id' => $center->id,
                    'is_student' => false,
                ]);

            $instructors = Instructor::factory()
                ->count(3)
                ->create([
                    'center_id' => $center->id,
                    'created_by' => $creator->id,
                ]);

            $courses = Course::where('center_id', $center->id)->get();

            foreach ($courses as $course) {
                $primary = $instructors->random();

                $course->update([
                    'primary_instructor_id' => $primary->id,
                ]);

                CourseInstructor::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'instructor_id' => $primary->id,
                    ],
                    [
                        'role' => 'lead',
                    ]
                );

                $additional = $instructors
                    ->reject(fn (Instructor $instructor): bool => $instructor->id === $primary->id)
                    ->shuffle()
                    ->take(random_int(0, max(0, $instructors->count() - 1)));

                foreach ($additional as $instructor) {
                    CourseInstructor::firstOrCreate(
                        [
                            'course_id' => $course->id,
                            'instructor_id' => $instructor->id,
                        ],
                        [
                            'role' => $this->randomRole(),
                        ]
                    );
                }
            }
        });
    }

    private function randomRole(): string
    {
        $roles = ['lead', 'assistant', 'guest'];

        return $roles[array_rand($roles)];
    }
}
