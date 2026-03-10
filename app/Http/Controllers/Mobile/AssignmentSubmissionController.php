<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Assignment\CreateSubmissionRequest;
use App\Http\Requests\Mobile\Assignment\UploadFileRequest;
use App\Http\Resources\Mobile\Assignment\AssignmentSubmissionResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use App\Services\Assessments\Contracts\AssignmentSubmissionServiceInterface;
use Illuminate\Http\JsonResponse;

class AssignmentSubmissionController extends Controller
{
    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService,
        private readonly AssignmentSubmissionServiceInterface $submissionService
    ) {}

    /**
     * Create or update a submission draft.
     */
    public function store(CreateSubmissionRequest $request, int $centerId, Assignment $assignment): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Assignment not found.',
                ],
            ], 404);
        }

        // Verify enrollment
        $enrollment = $student->enrollments()
            ->where('course_id', $assignment->course_id)
            ->where('center_id', $centerId)
            ->active()
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_ENROLLED',
                    'message' => 'You are not enrolled in this course.',
                ],
            ], 403);
        }

        if (! $this->assignmentService->canSubmit($assignment, $student)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_SUBMIT',
                    'message' => 'You cannot submit to this assignment.',
                ],
            ], 400);
        }

        /** @var array{submission_type?: string, text_content?: string, link_url?: string} $data */
        $data = $request->validated();

        // Check for existing submission
        $submission = $assignment->submissions()
            ->where('user_id', $student->id)
            ->first();

        try {
            if ($submission) {
                // Update existing draft
                if ($submission->status !== SubmissionStatus::Draft && $submission->status !== SubmissionStatus::Returned) {
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'ALREADY_SUBMITTED',
                            'message' => 'This submission has already been submitted.',
                        ],
                    ], 400);
                }

                $submission = $this->submissionService->updateDraft($submission, $data);
            } else {
                // Create new draft
                $submission = $this->submissionService->createDraft($assignment, $student, $enrollment);
                if (! empty($data)) {
                    $submission = $this->submissionService->updateDraft($submission, $data);
                }
            }

            $submission->load('files');

            return response()->json([
                'success' => true,
                'message' => $submission->wasRecentlyCreated ? 'Draft created' : 'Draft updated',
                'data' => new AssignmentSubmissionResource($submission),
            ], $submission->wasRecentlyCreated ? 201 : 200);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SUBMISSION_FAILED',
                    'message' => 'Failed to save submission.',
                ],
            ], 500);
        }
    }

    /**
     * Upload a file to a submission.
     */
    public function uploadFile(UploadFileRequest $request, int $centerId, AssignmentSubmission $submission): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        if ($submission->user_id !== $student->id || $submission->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Submission not found.',
                ],
            ], 404);
        }

        if ($submission->status !== SubmissionStatus::Draft && $submission->status !== SubmissionStatus::Returned) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_MODIFY',
                    'message' => 'Cannot modify a submitted assignment.',
                ],
            ], 400);
        }

        $assignment = $submission->assignment;

        // Check file count limit
        if ($assignment->max_files && $submission->files()->count() >= $assignment->max_files) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FILE_LIMIT_REACHED',
                    'message' => sprintf('Maximum of %d files allowed.', $assignment->max_files),
                ],
            ], 400);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        // Validate file type
        $extension = strtolower($file->getClientOriginalExtension());
        if ($assignment->allowed_file_types && ! in_array($extension, $assignment->allowed_file_types)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_FILE_TYPE',
                    'message' => 'File type not allowed. Allowed types: '.implode(', ', $assignment->allowed_file_types),
                ],
            ], 400);
        }

        // Validate file size
        $fileSizeMb = $file->getSize() / 1024 / 1024;
        if ($assignment->max_file_size_mb && $fileSizeMb > $assignment->max_file_size_mb) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FILE_TOO_LARGE',
                    'message' => sprintf('File size exceeds maximum of %dMB.', $assignment->max_file_size_mb),
                ],
            ], 400);
        }

        try {
            $submissionFile = $this->submissionService->attachFile($submission, $file);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $submissionFile->id,
                    'file_name' => $submissionFile->file_name,
                    'file_size_kb' => $submissionFile->file_size_kb,
                    'file_type' => $submissionFile->file_type,
                ],
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPLOAD_FAILED',
                    'message' => 'Failed to upload file.',
                ],
            ], 500);
        }
    }

    /**
     * Remove a file from a submission.
     */
    public function removeFile(int $centerId, AssignmentSubmission $submission, AssignmentSubmissionFile $file): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can modify submissions.',
                ],
            ], 403);
        }

        if ($submission->user_id !== $student->id || $submission->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Submission not found.',
                ],
            ], 404);
        }

        if ($file->assignment_submission_id !== $submission->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'File not found.',
                ],
            ], 404);
        }

        if ($submission->status !== SubmissionStatus::Draft && $submission->status !== SubmissionStatus::Returned) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_MODIFY',
                    'message' => 'Cannot modify a submitted assignment.',
                ],
            ], 400);
        }

        try {
            $this->submissionService->removeFile($file);

            return response()->json([
                'success' => true,
                'message' => 'File removed successfully',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REMOVE_FAILED',
                    'message' => 'Failed to remove file.',
                ],
            ], 500);
        }
    }

    /**
     * Submit the assignment.
     */
    public function submit(int $centerId, AssignmentSubmission $submission): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can submit assignments.',
                ],
            ], 403);
        }

        if ($submission->user_id !== $student->id || $submission->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Submission not found.',
                ],
            ], 404);
        }

        if ($submission->status !== SubmissionStatus::Draft && $submission->status !== SubmissionStatus::Returned) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_SUBMITTED',
                    'message' => 'This assignment has already been submitted.',
                ],
            ], 400);
        }

        // Validate submission has content
        $hasContent = $submission->text_content || $submission->link_url || $submission->files()->exists();
        if (! $hasContent) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EMPTY_SUBMISSION',
                    'message' => 'Submission must have content (text, link, or files).',
                ],
            ], 400);
        }

        try {
            $submission = $this->submissionService->submit($submission);
            $submission->load('files');

            return response()->json([
                'success' => true,
                'message' => 'Assignment submitted successfully',
                'data' => new AssignmentSubmissionResource($submission),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SUBMIT_FAILED',
                    'message' => 'Failed to submit assignment.',
                ],
            ], 500);
        }
    }
}
