<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly ParentProgressServiceInterface $progressService
    ) {}

    public function index(Request $request, int $student): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $enrollments = $this->progressService->getStudentEnrollments($parent, $student, $centerId);

        return response()->json([
            'success' => true,
            'data' => $enrollments->map(fn ($enrollment): array => [
                'id' => $enrollment->id,
                'course' => $enrollment->course ? [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->translate('title'),
                    'thumbnail' => $enrollment->course->thumbnail,
                ] : null,
                'status' => $enrollment->status->name,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                'expires_at' => $enrollment->expires_at?->toISOString(),
            ]),
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
