<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Instructors\ListInstructorsRequest;
use App\Http\Requests\Admin\Instructors\StoreInstructorRequest;
use App\Http\Requests\Admin\Instructors\UpdateInstructorRequest;
use App\Http\Resources\Admin\InstructorResource;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Admin\InstructorQueryService;
use App\Services\Centers\CenterScopeService;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function __construct(
        private readonly InstructorServiceInterface $instructorService,
        private readonly InstructorQueryService $queryService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function index(ListInstructorsRequest $request): JsonResponse
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
            'message' => 'Operation completed',
            'data' => InstructorResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreInstructorRequest $request): JsonResponse
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
        $data['created_by'] = (int) $admin->id;
        $data['avatar'] = $request->file('avatar');

        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, is_numeric($admin->center_id) ? (int) $admin->center_id : null);
            $data['center_id'] = (int) $admin->center_id;
        }

        $instructor = $this->instructorService->create($data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Instructor created successfully',
            'data' => new InstructorResource($instructor),
        ], 201);
    }

    public function show(Instructor $instructor): JsonResponse
    {
        /** @var User|null $admin */
        $admin = request()->user();

        if ($admin instanceof User && ! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminSameCenter($admin, $instructor);
        }

        $instructor->loadMissing(['center', 'creator', 'courses']);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new InstructorResource($instructor),
        ]);
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): JsonResponse
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

        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminSameCenter($admin, $instructor);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['avatar'] = $request->file('avatar');

        if (! $admin->hasRole('super_admin')) {
            $data['center_id'] = $instructor->center_id;
        }

        $updated = $this->instructorService->update($instructor, $data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Instructor updated successfully',
            'data' => new InstructorResource($updated),
        ]);
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        /** @var User|null $admin */
        $admin = request()->user();

        if ($admin instanceof User && ! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminSameCenter($admin, $instructor);
        }

        $this->instructorService->delete($instructor, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Instructor deleted successfully',
            'data' => null,
        ]);
    }
}
