<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService
    ) {}

    /**
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can view enrollments.',
                ],
            ], 403);
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->enrollmentService->paginateForStudent($user, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Enrollments retrieved successfully',
            'data' => EnrollmentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
