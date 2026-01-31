<?php

declare(strict_types=1);

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Pivots\CourseInstructor;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use App\Support\AuditActions;
use Illuminate\Database\Eloquent\Builder;

class CourseInstructorService implements CourseInstructorServiceInterface
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function assign(Course $course, Instructor $instructor, ?string $role = null, ?User $actor = null): CourseInstructor
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
            $course->primary_instructor_id = is_int($primaryId) ? $primaryId : (is_numeric($primaryId) ? (int) $primaryId : null);
            $course->save();
        }

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_INSTRUCTOR_ASSIGNED, [
            'instructor_id' => $instructor->id,
            'role' => $role,
        ]);

        return $assignment;
    }

    public function remove(Course $course, Instructor $instructor, ?User $actor = null): void
    {
        $assignment = CourseInstructor::query()
            ->forCourse($course)
            ->forInstructor($instructor)
            ->first();

        if ($assignment !== null) {
            $assignment->delete();
        }

        if ($course->primary_instructor_id === $instructor->id) {
            $replacement = CourseInstructor::query()
                ->forCourse($course)
                ->notDeleted()
                ->where(function (Builder $query) use ($instructor): void {
                    $query->where('instructor_id', '!=', $instructor->id);
                })
                ->first();

            $replacementId = $replacement?->instructor_id;
            $course->primary_instructor_id = is_int($replacementId) ? $replacementId : (is_numeric($replacementId) ? (int) $replacementId : null);
            $course->save();
        }

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_INSTRUCTOR_REMOVED, [
            'instructor_id' => $instructor->id,
        ]);
    }
}
