<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\Assignment\AssignmentDetailResource;
use App\Http\Resources\Mobile\Assignment\AssignmentListResource;
use App\Http\Resources\Mobile\Assignment\AssignmentSubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService
    ) {}

    /**
     * List available assignments for a course.
     */
    public function index(int $centerId, Course $course): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access assignments.',
                ],
            ], 403);
        }

        // Verify student is enrolled in this course
        $enrollment = $student->enrollments()
            ->where('course_id', $course->id)
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

        $assignments = Assignment::query()
            ->where('course_id', $course->id)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        // Add submission info for each assignment
        $assignments->each(function (Assignment $assignment) use ($student) {
            $submission = $assignment->submissions()
                ->where('user_id', $student->id)
                ->first();
            $assignment->setAttribute('my_submission', $submission);
            $assignment->setAttribute('can_submit', $this->assignmentService->canSubmit($assignment, $student));
        });

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => AssignmentListResource::collection($assignments),
        ]);
    }

    /**
     * Show assignment details.
     */
    public function show(int $centerId, Assignment $assignment): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access assignments.',
                ],
            ], 403);
        }

        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Assignment not found.',
                ],
            ], 404);
        }

        // Verify student is enrolled
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

        if (! $assignment->is_active || ! $this->assignmentService->isAvailable($assignment, $student)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_AVAILABLE',
                    'message' => 'This assignment is not currently available.',
                ],
            ], 400);
        }

        $submission = $assignment->submissions()
            ->where('user_id', $student->id)
            ->with('files')
            ->first();

        $assignment->setAttribute('my_submission', $submission);
        $assignment->setAttribute('can_submit', $this->assignmentService->canSubmit($assignment, $student));
        $assignment->setAttribute('is_late', $this->assignmentService->isLate($assignment));

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new AssignmentDetailResource($assignment),
        ]);
    }

    /**
     * Get student's submission for an assignment.
     */
    public function mySubmission(int $centerId, Assignment $assignment): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access submissions.',
                ],
            ], 403);
        }

        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Assignment not found.',
                ],
            ], 404);
        }

        $submission = $assignment->submissions()
            ->where('user_id', $student->id)
            ->with(['files', 'group.members.user'])
            ->first();

        if (! $submission) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NO_SUBMISSION',
                    'message' => 'You have not submitted this assignment yet.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new AssignmentSubmissionResource($submission),
        ]);
    }
}
