<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollments\StoreEnrollmentRequest;
use App\Http\Requests\Enrollments\UpdateEnrollmentStatusRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService
    ) {}

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        /** @var array{user_id:int,course_id:int,status:string} $data */
        $data = $request->validated();

        /** @var User $student */
        $student = User::findOrFail((int) $data['user_id']);
        /** @var Course $course */
        $course = Course::findOrFail((int) $data['course_id']);
        $admin = $request->user();

        if ($admin?->center_id !== null && (int) $admin->center_id !== (int) $course->center_id) {
            abort(403, 'You are not authorized to manage enrollments for this center.');
        }

        $enrollment = $this->enrollmentService->enroll($student, $course, $data['status'], $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully',
            'data' => new EnrollmentResource($enrollment->load(['course'])),
        ], 201);
    }

    public function update(UpdateEnrollmentStatusRequest $request, Enrollment $enrollment): JsonResponse
    {
        $admin = $request->user();
        if ($admin?->center_id !== null && (int) $admin->center_id !== (int) $enrollment->center_id) {
            abort(403, 'You are not authorized to manage enrollments for this center.');
        }

        /** @var array{status:string} $data */
        $data = $request->validated();

        $updated = $this->enrollmentService->updateStatus($enrollment, $data['status'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully',
            'data' => new EnrollmentResource($updated->load(['course'])),
        ]);
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $admin = request()->user();
        if ($admin?->center_id !== null && (int) $admin->center_id !== (int) $enrollment->center_id) {
            abort(403, 'You are not authorized to manage enrollments for this center.');
        }

        $this->enrollmentService->remove($enrollment, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment removed successfully',
            'data' => null,
        ], 204);
    }
}
