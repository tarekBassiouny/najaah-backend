<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assignment\GradeSubmissionRequest;
use App\Http\Requests\Admin\Assignment\ListSubmissionsRequest;
use App\Http\Requests\Admin\Assignment\ReturnSubmissionRequest;
use App\Http\Resources\Admin\Assignment\AssignmentSubmissionResource;
use App\Http\Resources\Admin\Assignment\AssignmentSubmissionDetailResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use App\Models\Center;
use App\Services\Assessments\Contracts\AssignmentSubmissionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentSubmissionController extends Controller
{
    public function __construct(
        private readonly AssignmentSubmissionServiceInterface $submissionService
    ) {}

    /**
     * List submissions for an assignment.
     *
     * @group Admin - Assignment Submissions
     */
    public function index(ListSubmissionsRequest $request, Center $center, Assignment $assignment): JsonResponse
    {
        $this->assertAssignmentInCenter($assignment, $center);

        $query = $assignment->submissions()
            ->with(['user', 'files', 'grader'])
            ->orderBy('submitted_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by passed
        if ($request->has('passed')) {
            $query->where('passed', $request->boolean('passed'));
        }

        // Filter by late
        if ($request->has('is_late')) {
            $query->where('is_late', $request->boolean('is_late'));
        }

        $perPage = $request->integer('per_page', 15);
        $submissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AssignmentSubmissionResource::collection($submissions->items()),
            'meta' => [
                'page' => $submissions->currentPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
                'last_page' => $submissions->lastPage(),
            ],
        ]);
    }

    /**
     * Show submission details.
     *
     * @group Admin - Assignment Submissions
     */
    public function show(Request $request, Center $center, AssignmentSubmission $submission): JsonResponse
    {
        $this->assertSubmissionInCenter($submission, $center);

        $submission->load(['user', 'files', 'grader', 'assignment', 'group.members.user']);

        return response()->json([
            'success' => true,
            'data' => new AssignmentSubmissionDetailResource($submission),
        ]);
    }

    /**
     * Grade a submission.
     *
     * @group Admin - Assignment Submissions
     */
    public function grade(GradeSubmissionRequest $request, Center $center, AssignmentSubmission $submission): JsonResponse
    {
        $this->assertSubmissionInCenter($submission, $center);

        if (! $submission->canBeGraded()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SUBMISSION_NOT_GRADABLE',
                    'message' => 'This submission cannot be graded in its current state.',
                ],
            ], 422);
        }

        $grader = $request->user();
        $submission = $this->submissionService->grade(
            $submission,
            $request->validated('score'),
            $request->validated('feedback', ''),
            $grader
        );

        return response()->json([
            'success' => true,
            'message' => 'Submission graded successfully.',
            'data' => new AssignmentSubmissionDetailResource($submission->load(['user', 'files', 'grader'])),
        ]);
    }

    /**
     * Return a submission for revision.
     *
     * @group Admin - Assignment Submissions
     */
    public function returnForRevision(ReturnSubmissionRequest $request, Center $center, AssignmentSubmission $submission): JsonResponse
    {
        $this->assertSubmissionInCenter($submission, $center);

        if ($submission->status !== SubmissionStatus::Submitted) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE',
                    'message' => 'Only submitted assignments can be returned for revision.',
                ],
            ], 422);
        }

        $grader = $request->user();
        $submission = $this->submissionService->returnForRevision(
            $submission,
            $request->validated('feedback'),
            $grader
        );

        return response()->json([
            'success' => true,
            'message' => 'Submission returned for revision.',
            'data' => new AssignmentSubmissionDetailResource($submission->load(['user', 'files', 'grader'])),
        ]);
    }

    /**
     * Download a submission file.
     *
     * @group Admin - Assignment Submissions
     */
    public function downloadFile(
        Request $request,
        Center $center,
        AssignmentSubmission $submission,
        AssignmentSubmissionFile $file
    ): StreamedResponse|JsonResponse {
        $this->assertSubmissionInCenter($submission, $center);

        if ($file->assignment_submission_id !== $submission->id) {
            abort(404, 'File not found in this submission.');
        }

        $disk = Storage::disk($file->storage_provider);

        if (! $disk->exists($file->file_path)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'The requested file could not be found.',
                ],
            ], 404);
        }

        return $disk->download($file->file_path, $file->file_name);
    }

    /**
     * Get submission statistics for an assignment.
     *
     * @group Admin - Assignment Submissions
     */
    public function statistics(Request $request, Center $center, Assignment $assignment): JsonResponse
    {
        $this->assertAssignmentInCenter($assignment, $center);

        $totalSubmissions = $assignment->submissions()->count();
        $submittedCount = $assignment->submissions()
            ->where('status', SubmissionStatus::Submitted)
            ->count();
        $gradedCount = $assignment->submissions()
            ->where('status', SubmissionStatus::Graded)
            ->count();
        $passedCount = $assignment->submissions()
            ->where('passed', true)
            ->count();
        $lateCount = $assignment->submissions()
            ->where('is_late', true)
            ->count();

        $averageScore = $assignment->submissions()
            ->where('status', SubmissionStatus::Graded)
            ->avg('score_after_penalty');

        $passRate = $gradedCount > 0
            ? round(($passedCount / $gradedCount) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'assignment_id' => $assignment->id,
                'total_submissions' => $totalSubmissions,
                'pending_grading' => $submittedCount,
                'graded' => $gradedCount,
                'passed' => $passedCount,
                'late_submissions' => $lateCount,
                'average_score' => $averageScore !== null ? round((float) $averageScore, 2) : null,
                'pass_rate' => $passRate,
            ],
        ]);
    }

    private function assertAssignmentInCenter(Assignment $assignment, Center $center): void
    {
        if ($assignment->center_id !== $center->id) {
            abort(404, 'Assignment not found in this center.');
        }
    }

    private function assertSubmissionInCenter(AssignmentSubmission $submission, Center $center): void
    {
        if ($submission->center_id !== $center->id) {
            abort(404, 'Submission not found in this center.');
        }
    }
}
