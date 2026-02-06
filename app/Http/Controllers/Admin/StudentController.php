<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\BulkUpdateStudentStatusRequest;
use App\Http\Requests\Admin\Students\ListStudentsRequest;
use App\Http\Requests\Admin\Students\StoreStudentRequest;
use App\Http\Requests\Admin\Students\UpdateStudentRequest;
use App\Http\Resources\Admin\Users\StudentResource;
use App\Models\User;
use App\Services\Admin\StudentQueryService;
use App\Services\Students\StudentService;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentQueryService $queryService,
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
        ], 204);
    }
}
