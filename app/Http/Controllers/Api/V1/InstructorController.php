<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Instructors\CreateInstructorAction;
use App\Actions\Instructors\DeleteInstructorAction;
use App\Actions\Instructors\ListInstructorsAction;
use App\Actions\Instructors\ShowInstructorAction;
use App\Actions\Instructors\UpdateInstructorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreInstructorRequest;
use App\Http\Requests\Instructor\UpdateInstructorRequest;
use App\Http\Resources\InstructorCollection;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function __construct(
        private readonly ListInstructorsAction $listAction,
        private readonly CreateInstructorAction $createAction,
        private readonly UpdateInstructorAction $updateAction,
        private readonly DeleteInstructorAction $deleteAction,
        private readonly ShowInstructorAction $showAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, (int) $request->query('per_page', 15));
        $paginator = $this->listAction->execute($perPage);
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
