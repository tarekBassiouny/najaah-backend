<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Parent\ParentQuizAttemptResource;
use App\Models\User;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizResultController extends Controller
{
    public function __construct(
        private readonly ParentProgressServiceInterface $progressService
    ) {}

    public function index(Request $request, int $student, int $course): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $attempts = $this->progressService->getQuizAttempts($parent, $student, $course, $centerId);

        return response()->json([
            'success' => true,
            'data' => ParentQuizAttemptResource::collection($attempts),
        ]);
    }

    public function show(Request $request, int $attempt): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();

        $attemptDetail = $this->progressService->getQuizAttemptDetail($parent, $attempt);

        return response()->json([
            'success' => true,
            'data' => new ParentQuizAttemptResource($attemptDetail),
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
