<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Enrollments\ListEnrollmentsRequest;
use App\Http\Requests\Admin\Enrollments\StoreEnrollmentRequest;
use App\Http\Requests\Admin\Enrollments\UpdateEnrollmentStatusRequest;
use App\Http\Resources\Admin\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService
    ) {}

    public function index(ListEnrollmentsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $enrollments = $this->enrollmentService->paginateForAdmin($admin, $filters);

        return response()->json([
            'success' => true,
            'data' => EnrollmentResource::collection($enrollments),
            'meta' => [
                'page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
            ],
        ]);
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();

        $this->enrollmentService->assertAdminCanAccess($admin, $enrollment);

        return response()->json([
            'success' => true,
            'data' => new EnrollmentResource($enrollment->load(['course', 'user', 'center'])),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{user_id:int,course_id:int,status:string} $data */
        $data = $request->validated();

        /** @var User $student */
        $student = User::findOrFail((int) $data['user_id']);
        /** @var Course $course */
        $course = Course::findOrFail((int) $data['course_id']);

        $enrollment = $this->enrollmentService->enroll($student, $course, $data['status'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully',
            'data' => new EnrollmentResource($enrollment->load(['course', 'user', 'center'])),
        ], 201);
    }

    public function update(UpdateEnrollmentStatusRequest $request, Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();

        /** @var array{status:string} $data */
        $data = $request->validated();

        $updated = $this->enrollmentService->updateStatus($enrollment, $data['status'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully',
            'data' => new EnrollmentResource($updated->load(['course', 'user', 'center'])),
        ]);
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();

        $this->enrollmentService->remove($enrollment, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment removed successfully',
            'data' => null,
        ], 204);
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }
}
