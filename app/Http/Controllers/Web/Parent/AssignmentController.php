<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly ParentProgressServiceInterface $progressService
    ) {}

    public function index(Request $request, int $student, int $course): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $submissions = $this->progressService->getAssignmentSubmissions($parent, $student, $course, $centerId);

        return response()->json([
            'success' => true,
            'data' => $submissions->map(fn ($submission): array => [
                'id' => $submission->id,
                'assignment' => $submission->assignment ? [
                    'id' => $submission->assignment->id,
                    'title' => $submission->assignment->translate('title'),
                ] : null,
                'status' => $submission->status->name,
                'submitted_at' => $submission->submitted_at?->toISOString(),
                'score' => $submission->score,
                'score_after_penalty' => $submission->score_after_penalty,
                'passed' => $submission->passed,
                'is_late' => $submission->is_late,
                'feedback' => $submission->feedback,
                'graded_at' => $submission->graded_at?->toISOString(),
            ]),
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
