<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\AssignmentGroupMember;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentSubmissionServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class AssignmentSubmissionService implements AssignmentSubmissionServiceInterface
{
    public function createDraft(Assignment $assignment, User $student, Enrollment $enrollment): AssignmentSubmission
    {
        $existingSubmission = $this->getStudentSubmission($assignment, $student);

        if ($existingSubmission !== null && $existingSubmission->canBeEdited()) {
            return $existingSubmission;
        }

        return AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'center_id' => $assignment->center_id,
            'submission_type' => SubmissionType::File,
            'status' => SubmissionStatus::Draft,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(AssignmentSubmission $submission, array $data): AssignmentSubmission
    {
        if (! $submission->canBeEdited()) {
            throw new \DomainException(json_encode([
                'code' => 'SUBMISSION_NOT_EDITABLE',
                'message' => 'This submission cannot be edited.',
            ]) ?: '');
        }

        $submission->update([
            'submission_type' => $data['submission_type'] ?? $submission->submission_type,
            'text_content' => $data['text_content'] ?? $submission->text_content,
            'link_url' => $data['link_url'] ?? $submission->link_url,
        ]);

        return $submission->fresh() ?? $submission;
    }

    public function attachFile(AssignmentSubmission $submission, UploadedFile $file): AssignmentSubmissionFile
    {
        if (! $submission->canBeEdited()) {
            throw new \DomainException(json_encode([
                'code' => 'SUBMISSION_NOT_EDITABLE',
                'message' => 'Cannot attach files to this submission.',
            ]) ?: '');
        }

        $assignment = $submission->assignment;

        // Check file count limit
        $currentFileCount = $submission->files()->count();
        if ($currentFileCount >= $assignment->max_files) {
            throw new \DomainException(json_encode([
                'code' => 'MAX_FILES_EXCEEDED',
                'message' => sprintf('Maximum of %d files allowed.', $assignment->max_files),
            ]) ?: '');
        }

        // Check file size
        $fileSizeMb = $file->getSize() / 1024 / 1024;
        if ($fileSizeMb > $assignment->max_file_size_mb) {
            throw new \DomainException(json_encode([
                'code' => 'FILE_TOO_LARGE',
                'message' => sprintf('File size exceeds %dMB limit.', $assignment->max_file_size_mb),
            ]) ?: '');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if ($assignment->allowed_file_types !== null && ! in_array($extension, $assignment->allowed_file_types, true)) {
            throw new \DomainException(json_encode([
                'code' => 'INVALID_FILE_TYPE',
                'message' => 'This file type is not allowed.',
            ]) ?: '');
        }

        // Store file
        $path = $file->store(
            sprintf('assignments/%d/submissions/%d', $assignment->id, $submission->id),
            'local'
        );

        return AssignmentSubmissionFile::create([
            'assignment_submission_id' => $submission->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size_kb' => (int) ($file->getSize() / 1024),
            'file_type' => $file->getMimeType() ?? 'application/octet-stream',
            'storage_provider' => 'local',
        ]);
    }

    public function removeFile(AssignmentSubmissionFile $file): void
    {
        $submission = $file->submission;

        if (! $submission->canBeEdited()) {
            throw new \DomainException(json_encode([
                'code' => 'SUBMISSION_NOT_EDITABLE',
                'message' => 'Cannot remove files from this submission.',
            ]) ?: '');
        }

        Storage::disk($file->storage_provider)->delete($file->file_path);
        $file->delete();
    }

    public function submit(AssignmentSubmission $submission): AssignmentSubmission
    {
        if (! $submission->canBeEdited()) {
            throw new \DomainException(json_encode([
                'code' => 'SUBMISSION_NOT_EDITABLE',
                'message' => 'This submission cannot be submitted.',
            ]) ?: '');
        }

        $assignment = $submission->assignment;
        $isLate = $assignment->isPastDue();
        $daysLate = 0;

        if ($isLate && $assignment->due_date !== null) {
            $daysLate = (int) now()->diffInDays($assignment->due_date);
        }

        $submission->update([
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'days_late' => $daysLate,
        ]);

        return $submission->fresh() ?? $submission;
    }

    public function grade(
        AssignmentSubmission $submission,
        float $score,
        string $feedback,
        User $grader
    ): AssignmentSubmission {
        if (! $submission->canBeGraded()) {
            throw new \DomainException(json_encode([
                'code' => 'SUBMISSION_NOT_GRADABLE',
                'message' => 'This submission cannot be graded.',
            ]) ?: '');
        }

        $assignment = $submission->assignment;
        $scoreAfterPenalty = $score;

        // Apply late penalty if applicable
        if ($submission->is_late && $submission->days_late > 0) {
            $penalty = $assignment->calculateLatePenalty($submission->days_late);
            $scoreAfterPenalty = max(0, $score - ($score * $penalty / 100));
        }

        $passed = ($scoreAfterPenalty / (float) $assignment->max_points * 100) >= (float) $assignment->passing_score;

        $submission->update([
            'status' => SubmissionStatus::Graded,
            'score' => $score,
            'score_after_penalty' => $scoreAfterPenalty,
            'passed' => $passed,
            'feedback' => $feedback,
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ]);

        return $submission->fresh() ?? $submission;
    }

    public function returnForRevision(
        AssignmentSubmission $submission,
        string $feedback,
        User $grader
    ): AssignmentSubmission {
        $submission->update([
            'status' => SubmissionStatus::Returned,
            'feedback' => $feedback,
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ]);

        return $submission->fresh() ?? $submission;
    }

    public function createGroup(Assignment $assignment, User $leader, string $name): AssignmentGroup
    {
        if (! $assignment->is_group_assignment) {
            throw new \DomainException(json_encode([
                'code' => 'NOT_GROUP_ASSIGNMENT',
                'message' => 'This assignment does not support groups.',
            ]) ?: '');
        }

        return DB::transaction(function () use ($assignment, $leader, $name): AssignmentGroup {
            $group = AssignmentGroup::create([
                'assignment_id' => $assignment->id,
                'name' => $name,
                'created_by' => $leader->id,
            ]);

            AssignmentGroupMember::create([
                'assignment_group_id' => $group->id,
                'user_id' => $leader->id,
                'role' => AssignmentGroupMember::ROLE_LEADER,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }

    public function joinGroup(AssignmentGroup $group, User $student): void
    {
        if ($group->hasMember($student->id)) {
            throw new \DomainException(json_encode([
                'code' => 'ALREADY_IN_GROUP',
                'message' => 'Student is already a member of this group.',
            ]) ?: '');
        }

        if ($group->isFull()) {
            throw new \DomainException(json_encode([
                'code' => 'GROUP_FULL',
                'message' => 'This group has reached maximum capacity.',
            ]) ?: '');
        }

        AssignmentGroupMember::create([
            'assignment_group_id' => $group->id,
            'user_id' => $student->id,
            'role' => AssignmentGroupMember::ROLE_MEMBER,
            'joined_at' => now(),
        ]);
    }

    public function leaveGroup(AssignmentGroup $group, User $student): void
    {
        $member = $group->members()->where('user_id', $student->id)->first();

        if ($member === null) {
            throw new \DomainException(json_encode([
                'code' => 'NOT_IN_GROUP',
                'message' => 'Student is not a member of this group.',
            ]) ?: '');
        }

        if ($member->isLeader() && $group->members()->count() > 1) {
            throw new \DomainException(json_encode([
                'code' => 'LEADER_CANNOT_LEAVE',
                'message' => 'Group leader cannot leave while other members remain.',
            ]) ?: '');
        }

        $member->delete();

        // Delete group if empty
        if ($group->members()->count() === 0) {
            $group->delete();
        }
    }

    public function getStudentSubmission(Assignment $assignment, User $student): ?AssignmentSubmission
    {
        return $assignment->submissions()
            ->where('user_id', $student->id)
            ->latest()
            ->first();
    }

    public function hasPassed(Assignment $assignment, User $student): bool
    {
        return $assignment->submissions()
            ->where('user_id', $student->id)
            ->where('passed', true)
            ->exists();
    }
}
