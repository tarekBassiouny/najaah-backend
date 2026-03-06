<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Education\ListGradesRequest;
use App\Http\Requests\Admin\Education\StoreGradeRequest;
use App\Http\Requests\Admin\Education\UpdateGradeRequest;
use App\Http\Resources\Admin\Education\GradeLookupResource;
use App\Http\Resources\Admin\Education\GradeResource;
use App\Models\Center;
use App\Models\Grade;
use App\Models\User;
use App\Services\Education\Contracts\GradeServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function __construct(
        private readonly GradeServiceInterface $gradeService
    ) {}

    public function index(ListGradesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $filters = $request->validated();
        $paginator = $this->gradeService->paginateForCenter($admin, (int) $center->id, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => GradeResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function lookup(ListGradesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $items = $this->gradeService->lookupForCenter($admin, (int) $center->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => GradeLookupResource::collection($items),
        ]);
    }

    public function store(StoreGradeRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $grade = $this->gradeService->createForCenter($admin, (int) $center->id, $request->validated());
        $grade->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Grade created successfully',
            'data' => new GradeResource($grade),
        ], 201);
    }

    public function show(Center $center, Grade $grade): JsonResponse
    {
        $this->assertBelongsToCenter((int) $grade->center_id, (int) $center->id, 'Grade not found.');
        $grade->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new GradeResource($grade),
        ]);
    }

    public function update(UpdateGradeRequest $request, Center $center, Grade $grade): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $updated = $this->gradeService->updateForCenter($admin, (int) $center->id, $grade, $request->validated());
        $updated->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully',
            'data' => new GradeResource($updated),
        ]);
    }

    public function destroy(Request $request, Center $center, Grade $grade): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $this->gradeService->deleteForCenter($admin, (int) $center->id, $grade);

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted successfully',
            'data' => null,
        ]);
    }

    private function requireAdmin(mixed $user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ],
        ], 401));
    }

    private function assertBelongsToCenter(int $entityCenterId, int $centerId, string $message): void
    {
        if ($entityCenterId === $centerId) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message,
            ],
        ], 404));
    }
}
