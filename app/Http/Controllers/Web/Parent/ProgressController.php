<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Parent\ParentProgressResource;
use App\Models\User;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(
        private readonly ParentProgressServiceInterface $progressService
    ) {}

    public function show(Request $request, int $student, int $course): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $progress = $this->progressService->getCourseProgress($parent, $student, $course, $centerId);

        return response()->json([
            'success' => true,
            'data' => new ParentProgressResource($progress),
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
