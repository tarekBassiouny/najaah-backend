<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Instructors\ShowInstructorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ListCenterInstructorsRequest;
use App\Http\Resources\InstructorCollection;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    public function __construct(
        private readonly ShowInstructorAction $showAction,
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function index(ListCenterInstructorsRequest $request): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        if (! $student instanceof User) {
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
        $centerId = is_numeric($student->center_id) ? (int) $student->center_id : null;
        $this->centerScopeService->assertCenterId($student, $centerId);

        $query = Instructor::query()
            ->where('center_id', $centerId)
            ->orderByDesc('id');

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where(static function ($builder) use ($term): void {
                    $builder->where('name_translations->en', 'like', '%'.$term.'%')
                        ->orWhere('name_translations->ar', 'like', '%'.$term.'%');
                });
            }
        }

        $paginator = $query->paginate($perPage);
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

    public function show(Instructor $instructor): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $this->centerScopeService->assertSameCenter($student, $instructor);

        $instructor = $this->showAction->execute($instructor);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new InstructorResource($instructor),
        ]);
    }
}
