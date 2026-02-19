<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Filters\Admin\ExtraViewRequestFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtraViews\ApproveExtraViewRequestRequest;
use App\Http\Requests\Admin\ExtraViews\BulkApproveExtraViewRequestsRequest;
use App\Http\Requests\Admin\ExtraViews\BulkGrantExtraViewsToStudentsRequest;
use App\Http\Requests\Admin\ExtraViews\BulkRejectExtraViewRequestsRequest;
use App\Http\Requests\Admin\ExtraViews\GrantExtraViewsToStudentRequest;
use App\Http\Requests\Admin\ExtraViews\ListExtraViewRequestsRequest;
use App\Http\Requests\Admin\ExtraViews\RejectExtraViewRequestRequest;
use App\Http\Resources\Admin\ExtraViews\ExtraViewRequestListResource;
use App\Http\Resources\Admin\ExtraViews\ExtraViewRequestResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\ExtraViewRequest;
use App\Models\User;
use App\Models\Video;
use App\Services\Admin\ExtraViewRequestQueryService;
use App\Services\Playback\ExtraViewRequestService;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class ExtraViewRequestController extends Controller
{
    public function __construct(
        private readonly ExtraViewRequestService $service,
        private readonly ExtraViewRequestQueryService $queryService
    ) {}

    /**
     * List extra view requests in system scope.
     */
    public function systemIndex(ListExtraViewRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $paginator = $this->queryService->paginate($admin, $request->filters());

        return $this->listResponse($paginator);
    }

    /**
     * List extra view requests in center scope.
     */
    public function centerIndex(ListExtraViewRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $paginator = $this->queryService->paginateForCenter(
            $admin,
            (int) $center->id,
            $this->forCenter($request->filters(), (int) $center->id)
        );

        return $this->listResponse($paginator);
    }

    /**
     * Grant extra views directly to a student in system scope.
     */
    public function systemGrantForStudent(
        GrantExtraViewsToStudentRequest $request,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertStudentUser($student);

        /** @var array{
         *   course_id:int,
         *   video_id:int,
         *   granted_views:int,
         *   reason:?string,
         *   decision_reason:?string
         * } $data
         */
        $data = $request->validated();

        $granted = $this->service->grantByAdmin(
            $admin,
            $student,
            $this->resolveCourse((int) $data['course_id']),
            $this->resolveVideo((int) $data['video_id']),
            (int) $data['granted_views'],
            $data['reason'] ?? null,
            $data['decision_reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Extra views granted successfully',
            'data' => new ExtraViewRequestResource($granted->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ], 201);
    }

    /**
     * Grant extra views directly to a student in center scope.
     */
    public function centerGrantForStudent(
        GrantExtraViewsToStudentRequest $request,
        Center $center,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertStudentBelongsToCenter($student, (int) $center->id);

        /** @var array{
         *   course_id:int,
         *   video_id:int,
         *   granted_views:int,
         *   reason:?string,
         *   decision_reason:?string
         * } $data
         */
        $data = $request->validated();

        $granted = $this->service->grantByAdmin(
            $admin,
            $student,
            $this->resolveCourse((int) $data['course_id']),
            $this->resolveVideo((int) $data['video_id']),
            (int) $data['granted_views'],
            $data['reason'] ?? null,
            $data['decision_reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Extra views granted successfully',
            'data' => new ExtraViewRequestResource($granted->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ], 201);
    }

    /**
     * Approve an extra view request in system scope.
     */
    public function systemApprove(
        ApproveExtraViewRequestRequest $request,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        $approved = $this->service->approve(
            $admin,
            $extraViewRequest,
            (int) $request->integer('granted_views'),
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully',
            'data' => new ExtraViewRequestResource($approved->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    /**
     * Approve an extra view request in center scope.
     */
    public function centerApprove(
        ApproveExtraViewRequestRequest $request,
        Center $center,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $extraViewRequest);

        $approved = $this->service->approve(
            $admin,
            $extraViewRequest,
            (int) $request->integer('granted_views'),
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully',
            'data' => new ExtraViewRequestResource($approved->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    /**
     * Reject an extra view request in system scope.
     */
    public function systemReject(
        RejectExtraViewRequestRequest $request,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        $rejected = $this->service->reject(
            $admin,
            $extraViewRequest,
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully',
            'data' => new ExtraViewRequestResource($rejected->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    /**
     * Reject an extra view request in center scope.
     */
    public function centerReject(
        RejectExtraViewRequestRequest $request,
        Center $center,
        ExtraViewRequest $extraViewRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $extraViewRequest);

        $rejected = $this->service->reject(
            $admin,
            $extraViewRequest,
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully',
            'data' => new ExtraViewRequestResource($rejected->loadMissing(['user', 'video', 'course', 'center', 'decider'])),
        ]);
    }

    /**
     * Bulk approve extra view requests in system scope.
     */
    public function systemBulkApprove(BulkApproveExtraViewRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,granted_views:int,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkApprove($admin, $data['request_ids'], (int) $data['granted_views'], $data['decision_reason'] ?? null, null);

        return $this->bulkResponse('Bulk extra view approval processed', $data['request_ids'], $result, 'approved');
    }

    /**
     * Bulk approve extra view requests in center scope.
     */
    public function centerBulkApprove(BulkApproveExtraViewRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,granted_views:int,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkApprove($admin, $data['request_ids'], (int) $data['granted_views'], $data['decision_reason'] ?? null, (int) $center->id);

        return $this->bulkResponse('Bulk extra view approval processed', $data['request_ids'], $result, 'approved');
    }

    /**
     * Bulk reject extra view requests in system scope.
     */
    public function systemBulkReject(BulkRejectExtraViewRequestsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkReject($admin, $data['request_ids'], $data['decision_reason'] ?? null, null);

        return $this->bulkResponse('Bulk extra view rejection processed', $data['request_ids'], $result, 'rejected');
    }

    /**
     * Bulk reject extra view requests in center scope.
     */
    public function centerBulkReject(BulkRejectExtraViewRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->bulkReject($admin, $data['request_ids'], $data['decision_reason'] ?? null, (int) $center->id);

        return $this->bulkResponse('Bulk extra view rejection processed', $data['request_ids'], $result, 'rejected');
    }

    /**
     * Bulk direct grant in system scope.
     */
    public function systemBulkGrant(BulkGrantExtraViewsToStudentsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{
         *   student_ids:array<int,int>,
         *   course_id:int,
         *   video_id:int,
         *   granted_views:int,
         *   reason:?string,
         *   decision_reason:?string
         * } $data
         */
        $data = $request->validated();

        $result = $this->bulkGrant(
            $admin,
            $data['student_ids'],
            $this->resolveCourse((int) $data['course_id']),
            $this->resolveVideo((int) $data['video_id']),
            (int) $data['granted_views'],
            $data['reason'] ?? null,
            $data['decision_reason'] ?? null,
            null
        );

        return $this->bulkGrantResponse('Bulk extra view grants processed', $data['student_ids'], $result);
    }

    /**
     * Bulk direct grant in center scope.
     */
    public function centerBulkGrant(BulkGrantExtraViewsToStudentsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{
         *   student_ids:array<int,int>,
         *   course_id:int,
         *   video_id:int,
         *   granted_views:int,
         *   reason:?string,
         *   decision_reason:?string
         * } $data
         */
        $data = $request->validated();

        $result = $this->bulkGrant(
            $admin,
            $data['student_ids'],
            $this->resolveCourse((int) $data['course_id']),
            $this->resolveVideo((int) $data['video_id']),
            (int) $data['granted_views'],
            $data['reason'] ?? null,
            $data['decision_reason'] ?? null,
            (int) $center->id
        );

        return $this->bulkGrantResponse('Bulk extra view grants processed', $data['student_ids'], $result);
    }

    private function assertRequestBelongsToCenter(Center $center, ExtraViewRequest $request): void
    {
        if ((int) $request->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Extra view request not found.',
                ],
            ], 404));
        }
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array{
     *   approved: array<int, ExtraViewRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>
     * }
     */
    private function bulkApprove(
        User $admin,
        array $requestIds,
        int $grantedViews,
        ?string $decisionReason,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = ExtraViewRequest::query()->whereIn('id', $uniqueIds);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $requests = $query->get()->keyBy('id');

        $result = [
            'approved' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $requestId) {
            $extraViewRequest = $requests->get($requestId);

            if (! $extraViewRequest instanceof ExtraViewRequest) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => 'Extra view request not found.',
                ];

                continue;
            }

            if ($extraViewRequest->status !== ExtraViewRequest::STATUS_PENDING) {
                $result['skipped'][] = $requestId;

                continue;
            }

            try {
                $result['approved'][] = $this->service->approve($admin, $extraViewRequest, $grantedViews, $decisionReason);
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @return array{
     *   rejected: array<int, ExtraViewRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>
     * }
     */
    private function bulkReject(
        User $admin,
        array $requestIds,
        ?string $decisionReason,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $requestIds)));
        $query = ExtraViewRequest::query()->whereIn('id', $uniqueIds);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $requests = $query->get()->keyBy('id');

        $result = [
            'rejected' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $requestId) {
            $extraViewRequest = $requests->get($requestId);

            if (! $extraViewRequest instanceof ExtraViewRequest) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => 'Extra view request not found.',
                ];

                continue;
            }

            if ($extraViewRequest->status !== ExtraViewRequest::STATUS_PENDING) {
                $result['skipped'][] = $requestId;

                continue;
            }

            try {
                $result['rejected'][] = $this->service->reject($admin, $extraViewRequest, $decisionReason);
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'request_id' => $requestId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, int|string>  $studentIds
     * @return array{
     *   granted: array<int, ExtraViewRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{student_id: int|string, reason: string}>
     * }
     */
    private function bulkGrant(
        User $admin,
        array $studentIds,
        Course $course,
        Video $video,
        int $grantedViews,
        ?string $reason,
        ?string $decisionReason,
        ?int $centerId
    ): array {
        $uniqueIds = array_values(array_unique(array_map('intval', $studentIds)));
        $query = User::query()
            ->whereIn('id', $uniqueIds)
            ->where('is_student', true);

        if ($centerId !== null) {
            $query->where('center_id', $centerId);
        }

        $students = $query->get()->keyBy('id');

        $result = [
            'granted' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($uniqueIds as $studentId) {
            $student = $students->get($studentId);

            if (! $student instanceof User) {
                $result['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found.',
                ];

                continue;
            }

            try {
                $result['granted'][] = $this->service->grantByAdmin(
                    $admin,
                    $student,
                    $course,
                    $video,
                    $grantedViews,
                    $reason,
                    $decisionReason
                );
            } catch (DomainException $exception) {
                if ($exception->errorCode() === ErrorCodes::PENDING_REQUEST_EXISTS) {
                    $result['skipped'][] = $studentId;

                    continue;
                }

                $result['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => $exception->getMessage(),
                ];
            } catch (\Throwable $exception) {
                $result['failed'][] = [
                    'student_id' => $studentId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function requireAdmin(HttpRequest $request): User
    {
        /** @var User|null $admin */
        $admin = $request->user();

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

    private function assertStudentUser(User $student): void
    {
        if (! $student->is_student) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => ErrorCodes::NOT_STUDENT,
                    'message' => 'The specified user is not a student.',
                ],
            ], 422));
        }
    }

    private function assertStudentBelongsToCenter(User $student, int $centerId): void
    {
        $this->assertStudentUser($student);

        if ((int) $student->center_id !== $centerId) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404));
        }
    }

    private function resolveCourse(int $courseId): Course
    {
        /** @var Course|null $course */
        $course = Course::query()->find($courseId);

        if ($course instanceof Course) {
            return $course;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => ErrorCodes::NOT_FOUND,
                'message' => 'Course not found.',
            ],
        ], 404));
    }

    private function resolveVideo(int $videoId): Video
    {
        /** @var Video|null $video */
        $video = Video::query()->find($videoId);

        if ($video instanceof Video) {
            return $video;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => ErrorCodes::NOT_FOUND,
                'message' => 'Video not found.',
            ],
        ], 404));
    }

    private function forCenter(ExtraViewRequestFilters $filters, int $centerId): ExtraViewRequestFilters
    {
        return new ExtraViewRequestFilters(
            page: $filters->page,
            perPage: $filters->perPage,
            status: $filters->status,
            centerId: $centerId,
            userId: $filters->userId,
            search: $filters->search,
            courseId: $filters->courseId,
            courseTitle: $filters->courseTitle,
            videoId: $filters->videoId,
            videoTitle: $filters->videoTitle,
            decidedBy: $filters->decidedBy,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo
        );
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator<ExtraViewRequest>  $paginator
     */
    private function listResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Extra view requests retrieved successfully',
            'data' => ExtraViewRequestListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @param  array<int, int|string>  $requestIds
     * @param  array{
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{request_id: int|string, reason: string}>,
     *   approved?: array<int, ExtraViewRequest>,
     *   rejected?: array<int, ExtraViewRequest>
     * }  $result
     */
    private function bulkResponse(string $message, array $requestIds, array $result, string $processedKey): JsonResponse
    {
        /** @var array<int, ExtraViewRequest> $processed */
        $processed = $result[$processedKey] ?? [];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $requestIds)))),
                    $processedKey => count($processed),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                $processedKey => ExtraViewRequestResource::collection(
                    collect($processed)->map(
                        fn (ExtraViewRequest $request) => $request->loadMissing(['user', 'video', 'course', 'center', 'decider'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * @param  array<int, int|string>  $studentIds
     * @param  array{
     *   granted: array<int, ExtraViewRequest>,
     *   skipped: array<int, int|string>,
     *   failed: array<int, array{student_id: int|string, reason: string}>
     * }  $result
     */
    private function bulkGrantResponse(string $message, array $studentIds, array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'counts' => [
                    'total' => count(array_values(array_unique(array_map('intval', $studentIds)))),
                    'granted' => count($result['granted']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'granted' => ExtraViewRequestResource::collection(
                    collect($result['granted'])->map(
                        fn (ExtraViewRequest $request) => $request->loadMissing(['user', 'video', 'course', 'center', 'decider'])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }
}
