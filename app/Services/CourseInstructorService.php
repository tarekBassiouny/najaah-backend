<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use Illuminate\Database\Eloquent\Builder;

class CourseInstructorService
{
    public function assign(Course $course, Instructor $instructor, ?string $role = null): CourseInstructor
    {
        $existing = CourseInstructor::withTrashed()
            ->where('course_id', $course->id)
            ->where('instructor_id', $instructor->id)
            ->first();

        if ($existing !== null) {
            $existing->restore();
            $existing->role = $role ?? $existing->role;
            $existing->save();

            $assignment = $existing;
        } else {
            $assignment = CourseInstructor::create([
                'course_id' => $course->id,
                'instructor_id' => $instructor->id,
                'role' => $role,
            ]);
        }

        if ($course->primary_instructor_id === null) {
            $primaryId = $instructor->getKey();
            $course->primary_instructor_id = is_int($primaryId)
                ? $primaryId
                : (is_numeric($primaryId) ? (int) $primaryId : null);
            $course->save();
        }

        return $assignment;
    }

    public function remove(Course $course, Instructor $instructor): void
    {
        $assignment = CourseInstructor::where('course_id', $course->id)
            ->where('instructor_id', $instructor->id)
            ->first();

        if ($assignment !== null) {
            $assignment->delete();
        }

        if ($course->primary_instructor_id === $instructor->id) {
            $replacement = CourseInstructor::where('course_id', $course->id)
                ->whereNull('deleted_at')
                ->where(function (Builder $query) use ($instructor): void {
                    $query->where('instructor_id', '!=', $instructor->id);
                })
                ->first();

            $replacementId = $replacement?->instructor_id;
            $course->primary_instructor_id = is_int($replacementId)
                ? $replacementId
                : (is_numeric($replacementId) ? (int) $replacementId : null);
            $course->save();
        }
    }
}
