<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface AssignmentSubmissionServiceInterface
{
    /**
     * Create a draft submission for an assignment.
     */
    public function createDraft(Assignment $assignment, User $student, Enrollment $enrollment): AssignmentSubmission;

    /**
     * Update a draft submission.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(AssignmentSubmission $submission, array $data): AssignmentSubmission;

    /**
     * Attach a file to a submission.
     */
    public function attachFile(AssignmentSubmission $submission, UploadedFile $file): AssignmentSubmissionFile;

    /**
     * Remove a file from a submission.
     */
    public function removeFile(AssignmentSubmissionFile $file): void;

    /**
     * Submit a draft for grading.
     */
    public function submit(AssignmentSubmission $submission): AssignmentSubmission;

    /**
     * Grade a submission.
     */
    public function grade(
        AssignmentSubmission $submission,
        float $score,
        string $feedback,
        User $grader
    ): AssignmentSubmission;

    /**
     * Return a submission for revision.
     */
    public function returnForRevision(
        AssignmentSubmission $submission,
        string $feedback,
        User $grader
    ): AssignmentSubmission;

    /**
     * Create a group for a group assignment.
     */
    public function createGroup(Assignment $assignment, User $leader, string $name): AssignmentGroup;

    /**
     * Join an existing group.
     */
    public function joinGroup(AssignmentGroup $group, User $student): void;

    /**
     * Leave a group.
     */
    public function leaveGroup(AssignmentGroup $group, User $student): void;

    /**
     * Get a student's submission for an assignment.
     */
    public function getStudentSubmission(Assignment $assignment, User $student): ?AssignmentSubmission;

    /**
     * Check if student has passed the assignment.
     */
    public function hasPassed(Assignment $assignment, User $student): bool;
}
