<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListStudentsRequest;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Http\Resources\Admin\StudentResource;
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

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();
        $paginator = $this->queryService->build($admin, $filters)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully',
            'data' => StudentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $student = $this->studentService->create($data);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ], 201);
    }

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
            'data' => new StudentResource($student),
        ]);
    }

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
