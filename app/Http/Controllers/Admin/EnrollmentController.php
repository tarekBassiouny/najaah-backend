<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\EnrollmentFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Enrollments\BulkEnrollmentRequest;
use App\Http\Requests\Admin\Enrollments\BulkUpdateEnrollmentStatusRequest;
use App\Http\Requests\Admin\Enrollments\ListEnrollmentsRequest;
use App\Http\Requests\Admin\Enrollments\StoreEnrollmentRequest;
use App\Http\Requests\Admin\Enrollments\UpdateEnrollmentStatusRequest;
use App\Http\Resources\Admin\EnrollmentResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService
    ) {}

    /**
     * List enrollments in system scope.
     */
    public function systemIndex(ListEnrollmentsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $enrollments = $this->enrollmentService->paginateForAdmin($admin, $request->filters());

        return $this->listResponse($enrollments);
    }

    /**
     * List enrollments in center scope.
     */
    public function centerIndex(ListEnrollmentsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $enrollments = $this->enrollmentService->paginateForAdmin(
            $admin,
            $this->forCenter($request->filters(), (int) $center->id)
        );

        return $this->listResponse($enrollments);
    }

    /**
     * Show an enrollment in system scope.
     */
    public function systemShow(Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->enrollmentService->assertAdminCanAccess($admin, $enrollment);

        return response()->json([
            'success' => true,
            'data' => new EnrollmentResource($enrollment->load(['course', 'user', 'center'])),
        ]);
    }

    /**
     * Show an enrollment in center scope.
     */
    public function centerShow(Center $center, Enrollment $enrollment): JsonResponse
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
     * Create an enrollment in system scope.
     */
    public function systemStore(StoreEnrollmentRequest $request): JsonResponse
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

    /**
     * Create an enrollment in center scope.
     */
    public function centerStore(StoreEnrollmentRequest $request, Center $center): JsonResponse
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
     * Bulk enroll students in system scope.
     */
    public function systemBulkEnroll(BulkEnrollmentRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{course_id:int,user_ids:array<int,int>} $data */
        $data = $request->validated();

        /** @var Course $course */
        $course = Course::findOrFail((int) $data['course_id']);

        $result = $this->enrollmentService->bulkEnroll(
            $admin,
            $course,
            (int) $course->center_id,
            $data['user_ids']
        );

        return $this->bulkEnrollResponse($data['user_ids'], $result);
    }

    /**
     * Bulk enroll students in center scope.
     */
    public function centerBulkEnroll(BulkEnrollmentRequest $request, Center $center): JsonResponse
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

        return $this->bulkEnrollResponse($data['user_ids'], $result);
    }

    /**
     * Bulk update enrollment statuses in system scope.
     */
    public function systemBulkUpdateStatus(BulkUpdateEnrollmentStatusRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{status:string,enrollment_ids:array<int,int>} $data */
        $data = $request->validated();

        $result = $this->enrollmentService->bulkUpdateStatus($admin, $data['status'], $data['enrollment_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment status update processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['enrollment_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => EnrollmentResource::collection(
                    collect($result['updated'])->map(
                        fn (Enrollment $enrollment) => $enrollment->load(['course', 'user', 'center'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Bulk update enrollment statuses in center scope.
     */
    public function centerBulkUpdateStatus(BulkUpdateEnrollmentStatusRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array{status:string,enrollment_ids:array<int,int>} $data */
        $data = $request->validated();

        $result = $this->enrollmentService->bulkUpdateStatus(
            $admin,
            $data['status'],
            $data['enrollment_ids'],
            (int) $center->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment status update processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $data['enrollment_ids'])))),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => EnrollmentResource::collection(
                    collect($result['updated'])->map(
                        fn (Enrollment $enrollment) => $enrollment->load(['course', 'user', 'center'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Update an enrollment status in system scope.
     */
    public function systemUpdate(UpdateEnrollmentStatusRequest $request, Enrollment $enrollment): JsonResponse
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

    /**
     * Update an enrollment status in center scope.
     */
    public function centerUpdate(UpdateEnrollmentStatusRequest $request, Center $center, Enrollment $enrollment): JsonResponse
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
     * Delete an enrollment in system scope.
     */
    public function systemDestroy(Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->enrollmentService->remove($enrollment, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment removed successfully',
            'data' => null,
        ], 200);
    }

    /**
     * Delete an enrollment in center scope.
     */
    public function centerDestroy(Center $center, Enrollment $enrollment): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertEnrollmentBelongsToCenter($center, $enrollment);

        $this->enrollmentService->remove($enrollment, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment removed successfully',
            'data' => null,
        ], 200);
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

    private function forCenter(EnrollmentFilters $filters, int $centerId): EnrollmentFilters
    {
        return new EnrollmentFilters(
            page: $filters->page,
            perPage: $filters->perPage,
            centerId: $centerId,
            courseId: $filters->courseId,
            userId: $filters->userId,
            search: $filters->search,
            status: $filters->status,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo
        );
    }

    /**
     * @param  array<int, int|string>  $userIds
     * @param  array{
     *   approved: array<int, Enrollment>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{user_id: int|string, reason: string}>
     * }  $result
     */
    private function bulkEnrollResponse(array $userIds, array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment processed',
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $userIds)))),
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
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator<Enrollment>  $enrollments
     */
    private function listResponse(LengthAwarePaginator $enrollments): JsonResponse
    {
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
}
