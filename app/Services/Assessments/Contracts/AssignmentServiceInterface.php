<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AssignmentServiceInterface
{
    /**
     * Create a new assignment for a course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Course $course, array $data, User $creator): Assignment;

    /**
     * Update an existing assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Assignment $assignment, array $data): Assignment;

    /**
     * Delete an assignment (soft delete).
     */
    public function delete(Assignment $assignment): void;

    /**
     * Check if an assignment is currently available for a student.
     */
    public function isAvailable(Assignment $assignment, User $student): bool;

    /**
     * Check if a student can submit to an assignment.
     */
    public function canSubmit(Assignment $assignment, User $student): bool;

    /**
     * Check if submission would be late.
     */
    public function isLate(Assignment $assignment): bool;

    /**
     * Calculate the number of days late for a submission.
     */
    public function getDaysLate(Assignment $assignment): int;

    /**
     * Get all assignments for a course.
     *
     * @return Collection<int, Assignment>
     */
    public function getAssignmentsForCourse(Course $course, bool $activeOnly = false): Collection;

    /**
     * Get assignment with submissions loaded.
     */
    public function getAssignmentWithSubmissions(Assignment $assignment): Assignment;
}
