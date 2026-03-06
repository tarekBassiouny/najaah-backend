<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Education\ListCollegesRequest;
use App\Http\Requests\Admin\Education\StoreCollegeRequest;
use App\Http\Requests\Admin\Education\UpdateCollegeRequest;
use App\Http\Resources\Admin\Education\CollegeLookupResource;
use App\Http\Resources\Admin\Education\CollegeResource;
use App\Models\Center;
use App\Models\College;
use App\Models\User;
use App\Services\Education\Contracts\CollegeServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function __construct(
        private readonly CollegeServiceInterface $collegeService
    ) {}

    public function index(ListCollegesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $paginator = $this->collegeService->paginateForCenter($admin, (int) $center->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => CollegeResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function lookup(ListCollegesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $items = $this->collegeService->lookupForCenter($admin, (int) $center->id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => CollegeLookupResource::collection($items),
        ]);
    }

    public function store(StoreCollegeRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $college = $this->collegeService->createForCenter($admin, (int) $center->id, $request->validated());
        $college->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'College created successfully',
            'data' => new CollegeResource($college),
        ], 201);
    }

    public function show(Center $center, College $college): JsonResponse
    {
        $this->assertBelongsToCenter((int) $college->center_id, (int) $center->id, 'College not found.');
        $college->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new CollegeResource($college),
        ]);
    }

    public function update(UpdateCollegeRequest $request, Center $center, College $college): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $updated = $this->collegeService->updateForCenter($admin, (int) $center->id, $college, $request->validated());
        $updated->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'College updated successfully',
            'data' => new CollegeResource($updated),
        ]);
    }

    public function destroy(Request $request, Center $center, College $college): JsonResponse
    {
        $admin = $this->requireAdmin($request->user());
        $this->collegeService->deleteForCenter($admin, (int) $center->id, $college);

        return response()->json([
            'success' => true,
            'message' => 'College deleted successfully',
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
