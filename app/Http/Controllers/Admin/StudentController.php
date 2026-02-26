<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\StudentFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\BulkUpdateStudentStatusRequest;
use App\Http\Requests\Admin\Students\ListStudentsRequest;
use App\Http\Requests\Admin\Students\StoreStudentRequest;
use App\Http\Requests\Admin\Students\UpdateStudentRequest;
use App\Http\Resources\Admin\Users\StudentResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Admin\StudentQueryService;
use App\Services\Analytics\AnalyticsStudentListSummaryService;
use App\Services\Students\StudentService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentQueryService $queryService,
        private readonly AnalyticsStudentListSummaryService $analyticsSummaryService,
        private readonly StudentService $studentService
    ) {}

    /**
     * List students.
     */
    public function index(ListStudentsRequest $request): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $filters = $request->filters();
        $paginator = $this->queryService->paginate($admin, $filters);
        $this->analyticsSummaryService->hydrate($paginator->items());

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully',
            'data' => StudentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * List students for a center.
     */
    public function centerIndex(ListStudentsRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $requestFilters = $request->filters();
        $filters = new StudentFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            centerId: (int) $center->id,
            status: $requestFilters->status,
            search: $requestFilters->search,
            studentName: $requestFilters->studentName,
            studentPhone: $requestFilters->studentPhone,
            studentEmail: $requestFilters->studentEmail,
            centerType: $requestFilters->centerType
        );

        $paginator = $this->queryService->paginateForCenter($admin, (int) $center->id, $filters);
        $this->analyticsSummaryService->hydrate($paginator->items());

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully',
            'data' => StudentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a student.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $student = $this->studentService->create($data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student->loadMissing([
                'center',
                'devices' => static function ($query): void {
                    $query->active()
                        ->orderByDesc('last_used_at')
                        ->orderByDesc('id');
                },
            ])),
        ], 201);
    }

    /**
     * Create a student in a center.
     */
    public function centerStore(StoreStudentRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $student = $this->studentService->createForCenter($center, $data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student->loadMissing([
                'center',
                'devices' => static function ($query): void {
                    $query->active()
                        ->orderByDesc('last_used_at')
                        ->orderByDesc('id');
                },
            ])),
        ], 201);
    }

    /**
     * Update a student.
     */
    public function update(UpdateStudentRequest $request, User $user): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $student = $this->studentService->update($user, $data, $admin);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student->loadMissing([
                'center',
                'devices' => static function ($query): void {
                    $query->active()
                        ->orderByDesc('last_used_at')
                        ->orderByDesc('id');
                },
            ])),
        ]);
    }

    /**
     * Update a center student.
     */
    public function centerUpdate(UpdateStudentRequest $request, Center $center, User $user): JsonResponse
    {
        $this->assertStudentBelongsToCenter($center, $user);

        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $student = $this->studentService->update($user, $data, $admin);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student->loadMissing([
                'center',
                'devices' => static function ($query): void {
                    $query->active()
                        ->orderByDesc('last_used_at')
                        ->orderByDesc('id');
                },
            ])),
        ]);
    }

    /**
     * Bulk update student statuses.
     */
    public function bulkUpdateStatus(BulkUpdateStudentStatusRequest $request): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        /** @var array{status:int,student_ids:array<int,int>} $data */
        $data = $request->validated();
        $result = $this->studentService->bulkUpdateStatus($admin, (int) $data['status'], $data['student_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Bulk student status update processed',
            'data' => [
                'counts' => [
                    'total' => count($data['student_ids']),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => StudentResource::collection(
                    collect($result['updated'])->map(
                        fn (User $student) => $student->loadMissing([
                            'center',
                            'devices' => static function ($query): void {
                                $query->active()
                                    ->orderByDesc('last_used_at')
                                    ->orderByDesc('id');
                            },
                        ])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Bulk update center student statuses.
     */
    public function centerBulkUpdateStatus(BulkUpdateStudentStatusRequest $request, Center $center): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        /** @var array{status:int,student_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['student_ids'])));
        $allowedIds = User::query()
            ->where('is_student', true)
            ->where('center_id', (int) $center->id)
            ->whereIn('id', $requestedIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $rejectedIds = array_values(array_diff($requestedIds, $allowedIds));

        $result = $this->studentService->bulkUpdateStatus($admin, (int) $data['status'], $allowedIds);
        foreach ($rejectedIds as $rejectedId) {
            $result['failed'][] = [
                'student_id' => $rejectedId,
                'reason' => 'Student not found.',
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk student status update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($result['updated']),
                    'skipped' => count($result['skipped']),
                    'failed' => count($result['failed']),
                ],
                'updated' => StudentResource::collection(
                    collect($result['updated'])->map(
                        fn (User $student) => $student->loadMissing([
                            'center',
                            'devices' => static function ($query): void {
                                $query->active()
                                    ->orderByDesc('last_used_at')
                                    ->orderByDesc('id');
                            },
                        ])
                    )
                ),
                'skipped' => $result['skipped'],
                'failed' => $result['failed'],
            ],
        ]);
    }

    /**
     * Delete a student.
     */
    public function destroy(User $user): JsonResponse
    {
        /** @var User|null $admin */
        $admin = request()->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $this->studentService->delete($user, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 200);
    }

    /**
     * Delete a center student.
     */
    public function centerDestroy(Center $center, User $user): JsonResponse
    {
        $this->assertStudentBelongsToCenter($center, $user);

        /** @var User|null $admin */
        $admin = request()->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $this->studentService->delete($user, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 200);
    }

    private function assertStudentBelongsToCenter(Center $center, User $user): void
    {
        if ((int) $user->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404));
        }
    }
}
