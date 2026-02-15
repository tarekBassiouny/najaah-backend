<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Instructors\ListInstructorsRequest;
use App\Http\Requests\Admin\Instructors\StoreInstructorRequest;
use App\Http\Requests\Admin\Instructors\UpdateInstructorRequest;
use App\Http\Resources\Admin\InstructorResource;
use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Admin\InstructorQueryService;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function __construct(
        private readonly InstructorServiceInterface $instructorService,
        private readonly InstructorQueryService $queryService
    ) {}

    /**
     * List instructors.
     */
    public function index(ListInstructorsRequest $request, Center $center): JsonResponse
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
        $paginator = $this->queryService->paginateForCenter($admin, (int) $center->id, $filters);

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

    /**
     * Create an instructor.
     */
    public function store(StoreInstructorRequest $request, Center $center): JsonResponse
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
        $data['center_id'] = (int) $center->id;

        $instructor = $this->instructorService->create($data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Instructor created successfully',
            'data' => new InstructorResource($instructor),
        ], 201);
    }

    /**
     * Show an instructor.
     */
    public function show(Center $center, Instructor $instructor): JsonResponse
    {
        $this->assertInstructorBelongsToCenter($center, $instructor);

        $instructor->loadMissing(['center', 'creator', 'courses']);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new InstructorResource($instructor),
        ]);
    }

    /**
     * Update an instructor.
     */
    public function update(UpdateInstructorRequest $request, Center $center, Instructor $instructor): JsonResponse
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

        $this->assertInstructorBelongsToCenter($center, $instructor);

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['avatar'] = $request->file('avatar');
        $data['center_id'] = (int) $center->id;

        $updated = $this->instructorService->update($instructor, $data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Instructor updated successfully',
            'data' => new InstructorResource($updated),
        ]);
    }

    /**
     * Delete an instructor.
     */
    public function destroy(Center $center, Instructor $instructor): JsonResponse
    {
        /** @var User|null $admin */
        $admin = request()->user();
        $this->assertInstructorBelongsToCenter($center, $instructor);

        $this->instructorService->delete($instructor, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Instructor deleted successfully',
            'data' => null,
        ]);
    }

    private function assertInstructorBelongsToCenter(Center $center, Instructor $instructor): void
    {
        if ((int) $instructor->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Instructor not found.',
                ],
            ], 404));
        }
    }
}
