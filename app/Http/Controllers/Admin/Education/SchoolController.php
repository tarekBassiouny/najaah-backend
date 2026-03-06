<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Education\ListSchoolsRequest;
use App\Http\Requests\Admin\Education\StoreSchoolRequest;
use App\Http\Requests\Admin\Education\UpdateSchoolRequest;
use App\Http\Resources\Admin\Education\SchoolLookupResource;
use App\Http\Resources\Admin\Education\SchoolResource;
use App\Models\Center;
use App\Models\School;
use App\Models\User;
use App\Services\Education\Contracts\SchoolServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolServiceInterface $schoolService
    ) {}

    public function index(ListSchoolsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $paginator = $this->schoolService->paginateForCenter($admin, (int) $center->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => SchoolResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function lookup(ListSchoolsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $items = $this->schoolService->lookupForCenter($admin, (int) $center->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => SchoolLookupResource::collection($items),
        ]);
    }

    public function store(StoreSchoolRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $school = $this->schoolService->createForCenter($admin, (int) $center->id, $request->validated());
        $school->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'School created successfully',
            'data' => new SchoolResource($school),
        ], 201);
    }

    public function show(Center $center, School $school): JsonResponse
    {
        $this->assertBelongsToCenter((int) $school->center_id, (int) $center->id, 'School not found.');
        $school->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new SchoolResource($school),
        ]);
    }

    public function update(UpdateSchoolRequest $request, Center $center, School $school): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $updated = $this->schoolService->updateForCenter($admin, (int) $center->id, $school, $request->validated());
        $updated->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully',
            'data' => new SchoolResource($updated),
        ]);
    }

    public function destroy(Request $request, Center $center, School $school): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $this->schoolService->deleteForCenter($admin, (int) $center->id, $school);

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully',
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
