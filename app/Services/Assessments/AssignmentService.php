<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

final class AssignmentService implements AssignmentServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Course $course, array $data, User $creator): Assignment
    {
        return Assignment::create([
            'center_id' => $course->center_id,
            'course_id' => $course->id,
            'title_translations' => $data['title_translations'],
            'description_translations' => $data['description_translations'] ?? null,
            'attachable_type' => $data['attachable_type'] ?? null,
            'attachable_id' => $data['attachable_id'] ?? null,
            'submission_types' => $data['submission_types'] ?? [0], // Default to File
            'allowed_file_types' => $data['allowed_file_types'] ?? ['pdf', 'doc', 'docx'],
            'max_file_size_mb' => $data['max_file_size_mb'] ?? 10,
            'max_files' => $data['max_files'] ?? 5,
            'is_group_assignment' => $data['is_group_assignment'] ?? false,
            'max_group_size' => $data['max_group_size'] ?? null,
            'max_points' => $data['max_points'] ?? 100.00,
            'passing_score' => $data['passing_score'] ?? 60.00,
            'is_required' => $data['is_required'] ?? false,
            'is_active' => $data['is_active'] ?? false,
            'due_date' => $data['due_date'] ?? null,
            'late_submission_allowed' => $data['late_submission_allowed'] ?? false,
            'late_penalty_percent' => $data['late_penalty_percent'] ?? 0.00,
            'available_from' => $data['available_from'] ?? null,
            'available_until' => $data['available_until'] ?? null,
            'order_index' => $data['order_index'] ?? 0,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->update([
            'title_translations' => $data['title_translations'] ?? $assignment->title_translations,
            'description_translations' => $data['description_translations'] ?? $assignment->description_translations,
            'attachable_type' => $data['attachable_type'] ?? $assignment->attachable_type,
            'attachable_id' => $data['attachable_id'] ?? $assignment->attachable_id,
            'submission_types' => $data['submission_types'] ?? $assignment->submission_types,
            'allowed_file_types' => $data['allowed_file_types'] ?? $assignment->allowed_file_types,
            'max_file_size_mb' => $data['max_file_size_mb'] ?? $assignment->max_file_size_mb,
            'max_files' => $data['max_files'] ?? $assignment->max_files,
            'is_group_assignment' => $data['is_group_assignment'] ?? $assignment->is_group_assignment,
            'max_group_size' => $data['max_group_size'] ?? $assignment->max_group_size,
            'max_points' => $data['max_points'] ?? $assignment->max_points,
            'passing_score' => $data['passing_score'] ?? $assignment->passing_score,
            'is_required' => $data['is_required'] ?? $assignment->is_required,
            'is_active' => $data['is_active'] ?? $assignment->is_active,
            'due_date' => $data['due_date'] ?? $assignment->due_date,
            'late_submission_allowed' => $data['late_submission_allowed'] ?? $assignment->late_submission_allowed,
            'late_penalty_percent' => $data['late_penalty_percent'] ?? $assignment->late_penalty_percent,
            'available_from' => $data['available_from'] ?? $assignment->available_from,
            'available_until' => $data['available_until'] ?? $assignment->available_until,
            'order_index' => $data['order_index'] ?? $assignment->order_index,
        ]);

        return $assignment->fresh() ?? $assignment;
    }

    public function delete(Assignment $assignment): void
    {
        $assignment->delete();
    }

    public function isAvailable(Assignment $assignment, User $student): bool
    {
        return $assignment->isAvailable();
    }

    public function canSubmit(Assignment $assignment, User $student): bool
    {
        if (! $this->isAvailable($assignment, $student)) {
            // Check if late submission is allowed
            if (! $assignment->late_submission_allowed) {
                return false;
            }

            // Check if we're past the hard cutoff
            if ($assignment->available_until !== null && now()->gt($assignment->available_until)) {
                return false;
            }
        }

        return true;
    }

    public function isLate(Assignment $assignment): bool
    {
        return $assignment->isPastDue();
    }

    public function getDaysLate(Assignment $assignment): int
    {
        if ($assignment->due_date === null) {
            return 0;
        }

        if (! $this->isLate($assignment)) {
            return 0;
        }

        return (int) now()->diffInDays($assignment->due_date);
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignmentsForCourse(Course $course, bool $activeOnly = false): Collection
    {
        $query = Assignment::where('course_id', $course->id)
            ->orderBy('order_index');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function getAssignmentWithSubmissions(Assignment $assignment): Assignment
    {
        return $assignment->load(['submissions' => function ($query): void {
            $query->with(['user', 'files'])->orderBy('submitted_at', 'desc');
        }]);
    }
}
