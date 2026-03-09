<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Assessments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assignment\ListAssignmentsRequest;
use App\Http\Requests\Admin\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Admin\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\Admin\Assignment\AssignmentDetailResource;
use App\Http\Resources\Admin\Assignment\AssignmentResource;
use App\Models\Assignment;
use App\Models\Center;
use App\Models\Course;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService
    ) {}

    /**
     * List assignments for a course.
     *
     * @group Admin - Assignments
     */
    public function index(ListAssignmentsRequest $request, Center $center, Course $course): JsonResponse
    {
        $this->assertCourseInCenter($course, $center);

        $assignments = $this->assignmentService->getAssignmentsForCourse(
            $course,
            $request->boolean('active_only', false)
        );

        return response()->json([
            'success' => true,
            'data' => AssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Store a new assignment.
     *
     * @group Admin - Assignments
     */
    public function store(StoreAssignmentRequest $request, Center $center, Course $course): JsonResponse
    {
        $this->assertCourseInCenter($course, $center);

        $admin = $request->user();
        $assignment = $this->assignmentService->create($course, $request->validated(), $admin);

        return response()->json([
            'success' => true,
            'message' => 'Assignment created successfully.',
            'data' => new AssignmentDetailResource($assignment),
        ], 201);
    }

    /**
     * Show assignment details.
     *
     * @group Admin - Assignments
     */
    public function show(Request $request, Center $center, Assignment $assignment): JsonResponse
    {
        $this->assertAssignmentInCenter($assignment, $center);

        $assignment = $this->assignmentService->getAssignmentWithSubmissions($assignment);

        return response()->json([
            'success' => true,
            'data' => new AssignmentDetailResource($assignment),
        ]);
    }

    /**
     * Update an assignment.
     *
     * @group Admin - Assignments
     */
    public function update(UpdateAssignmentRequest $request, Center $center, Assignment $assignment): JsonResponse
    {
        $this->assertAssignmentInCenter($assignment, $center);

        $assignment = $this->assignmentService->update($assignment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.',
            'data' => new AssignmentDetailResource($assignment),
        ]);
    }

    /**
     * Delete an assignment.
     *
     * @group Admin - Assignments
     */
    public function destroy(Request $request, Center $center, Assignment $assignment): JsonResponse
    {
        $this->assertAssignmentInCenter($assignment, $center);

        $this->assignmentService->delete($assignment);

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully.',
        ]);
    }

    private function assertCourseInCenter(Course $course, Center $center): void
    {
        if ($course->center_id !== $center->id) {
            abort(404, 'Course not found in this center.');
        }
    }

    private function assertAssignmentInCenter(Assignment $assignment, Center $center): void
    {
        if ($assignment->center_id !== $center->id) {
            abort(404, 'Assignment not found in this center.');
        }
    }
}
