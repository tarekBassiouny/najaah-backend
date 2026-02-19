<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\EnrollmentFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Enrollments\BulkEnrollmentRequest;
use App\Http\Requests\Admin\Enrollments\ListEnrollmentsRequest;
use App\Http\Requests\Admin\Enrollments\StoreEnrollmentRequest;
use App\Http\Requests\Admin\Enrollments\UpdateEnrollmentStatusRequest;
use App\Http\Resources\Admin\EnrollmentResource;
use App\Models\Center;
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

    /**
     * List enrollments.
     */
    public function index(ListEnrollmentsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $requestFilters = $request->filters();
        $filters = new EnrollmentFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            centerId: (int) $center->id,
            courseId: $requestFilters->courseId,
            userId: $requestFilters->userId,
            status: $requestFilters->status
        );
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

    /**
     * Show an enrollment.
     */
    public function show(Center $center, Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertEnrollmentBelongsToCenter($center, $enrollment);
        $this->enrollmentService->assertAdminCanAccess($admin, $enrollment);

        return response()->json([
            'success' => true,
            'data' => new EnrollmentResource($enrollment->load(['course', 'user', 'center'])),
        ]);
    }

    /**
     * Create an enrollment.
     */
    public function store(StoreEnrollmentRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{user_id:int,course_id:int,status:string} $data */
        $data = $request->validated();

        /** @var User $student */
        $student = User::findOrFail((int) $data['user_id']);
        /** @var Course $course */
        $course = Course::findOrFail((int) $data['course_id']);
        $this->assertCourseBelongsToCenter($center, $course);

        $enrollment = $this->enrollmentService->enroll($student, $course, $data['status'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully',
            'data' => new EnrollmentResource($enrollment->load(['course', 'user', 'center'])),
        ], 201);
    }

    /**
     * Bulk approve enrollments.
     */
    public function bulk(BulkEnrollmentRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{course_id:int,user_ids:array<int,int>} $data */
        $data = $request->validated();

        /** @var Course $course */
        $course = Course::findOrFail((int) $data['course_id']);
        $this->assertCourseBelongsToCenter($center, $course);

        $result = $this->enrollmentService->bulkEnroll(
            $admin,
            $course,
            (int) $center->id,
            $data['user_ids']
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment processed',
            'data' => [
                'counts' => [
                    'total' => count($data['user_ids']),
                    'approved' => count($result['approved']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'approved' => EnrollmentResource::collection(
                    collect($result['approved'])->map(
                        fn (Enrollment $enrollment) => $enrollment->load(['course', 'user', 'center'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Update an enrollment status.
     */
    public function update(UpdateEnrollmentStatusRequest $request, Center $center, Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertEnrollmentBelongsToCenter($center, $enrollment);

        /** @var array{status:string} $data */
        $data = $request->validated();

        $updated = $this->enrollmentService->updateStatus($enrollment, $data['status'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully',
            'data' => new EnrollmentResource($updated->load(['course', 'user', 'center'])),
        ]);
    }

    /**
     * Delete an enrollment.
     */
    public function destroy(Center $center, Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertEnrollmentBelongsToCenter($center, $enrollment);

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

    private function assertEnrollmentBelongsToCenter(Center $center, Enrollment $enrollment): void
    {
        if ((int) $enrollment->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Enrollment not found.',
                ],
            ], 404));
        }
    }

    private function assertCourseBelongsToCenter(Center $center, Course $course): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404));
        }
    }
}
