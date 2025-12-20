<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Instructors\CreateInstructorAction;
use App\Actions\Instructors\DeleteInstructorAction;
use App\Actions\Instructors\ShowInstructorAction;
use App\Actions\Instructors\UpdateInstructorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListInstructorsRequest;
use App\Http\Requests\Instructor\StoreInstructorRequest;
use App\Http\Requests\Instructor\UpdateInstructorRequest;
use App\Http\Resources\InstructorCollection;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Admin\InstructorQueryService;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function __construct(
        private readonly CreateInstructorAction $createAction,
        private readonly UpdateInstructorAction $updateAction,
        private readonly DeleteInstructorAction $deleteAction,
        private readonly ShowInstructorAction $showAction,
        private readonly InstructorQueryService $queryService
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

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();
        $paginator = $this->queryService->build($admin, $filters)->paginate($perPage);
        $items = (new InstructorCollection(collect($paginator->items())))->toArray($request);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreInstructorRequest $request): JsonResponse
    {
        if ($request->user() === null) {
            abort(401);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['created_by'] = (int) $request->user()->id;
        $data['avatar'] = $request->file('avatar');

        $instructor = $this->createAction->execute($data);

        return response()->json([
            'success' => true,
            'message' => 'Instructor created successfully',
            'data' => new InstructorResource($instructor),
        ], 201);
    }

    public function show(Instructor $instructor): JsonResponse
    {
        $instructor = $this->showAction->execute($instructor);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new InstructorResource($instructor),
        ]);
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['avatar'] = $request->file('avatar');
        $updated = $this->updateAction->execute($instructor, $data);

        return response()->json([
            'success' => true,
            'message' => 'Instructor updated successfully',
            'data' => new InstructorResource($updated),
        ]);
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        $this->deleteAction->execute($instructor);

        return response()->json([
            'success' => true,
            'message' => 'Instructor deleted successfully',
            'data' => null,
        ]);
    }
}
