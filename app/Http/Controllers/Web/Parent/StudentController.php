<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Parent\ParentStudentResource;
use App\Models\User;
use App\Services\Parents\Contracts\ParentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly ParentServiceInterface $parentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $links = $this->parentService->getLinkedStudents($parent, $centerId);

        return response()->json([
            'success' => true,
            'data' => ParentStudentResource::collection($links),
        ]);
    }

    public function show(Request $request, int $student): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $studentUser = $this->parentService->getStudentDetail($parent, $student, $centerId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $studentUser->id,
                'name' => $studentUser->name,
                'phone' => $studentUser->phone,
                'center' => $studentUser->center ? [
                    'id' => $studentUser->center->id,
                    'name' => $studentUser->center->name,
                ] : null,
                'grade' => $studentUser->grade ? [
                    'id' => $studentUser->grade->id,
                    'name' => $studentUser->grade->translate('name'),
                ] : null,
                'school' => $studentUser->school ? [
                    'id' => $studentUser->school->id,
                    'name' => $studentUser->school->translate('name'),
                ] : null,
            ],
        ]);
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
