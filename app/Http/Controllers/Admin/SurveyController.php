<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SurveyScopeType;
use App\Filters\Admin\SurveyFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Surveys\AssignSurveyRequest;
use App\Http\Requests\Admin\Surveys\ListSurveysRequest;
use App\Http\Requests\Admin\Surveys\ListSurveyTargetStudentsRequest;
use App\Http\Requests\Admin\Surveys\StoreSurveyRequest;
use App\Http\Requests\Admin\Surveys\UpdateSurveyRequest;
use App\Http\Resources\Admin\SurveyAnalyticsResource;
use App\Http\Resources\Admin\SurveyResource;
use App\Http\Resources\Admin\SurveyTargetStudentResource;
use App\Models\Center;
use App\Models\Survey;
use App\Models\User;
use App\Services\Surveys\Contracts\SurveyAssignmentServiceInterface;
use App\Services\Surveys\Contracts\SurveyServiceInterface;
use App\Services\Surveys\SurveyTargetStudentService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(
        private readonly SurveyServiceInterface $surveyService,
        private readonly SurveyAssignmentServiceInterface $assignmentService,
        private readonly SurveyTargetStudentService $targetStudentService
    ) {}

    /**
     * List system-scoped surveys (Najaah App scope).
     */
    public function systemIndex(ListSurveysRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $requestFilters = $request->filters();
        $filters = new SurveyFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            scopeType: SurveyScopeType::System->value,
            centerId: null,
            isActive: $requestFilters->isActive,
            type: $requestFilters->type
        );

        $paginator = $this->surveyService->paginate($filters, $admin);

        return $this->surveyListResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * List center-scoped surveys for a branded center.
     */
    public function centerIndex(ListSurveysRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $requestFilters = $request->filters();
        $filters = new SurveyFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            scopeType: SurveyScopeType::Center->value,
            centerId: (int) $center->id,
            isActive: $requestFilters->isActive,
            type: $requestFilters->type
        );

        $paginator = $this->surveyService->paginate($filters, $admin);

        return $this->surveyListResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * List eligible students for system survey assignments.
     */
    public function systemTargetStudents(ListSurveyTargetStudentsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $filters = $request->filters();

        $paginator = $this->targetStudentService->paginate(
            actor: $admin,
            scopeType: SurveyScopeType::System,
            centerId: $filters['center_id'],
            status: $filters['status'],
            search: $filters['search'],
            perPage: $filters['per_page'],
            page: $filters['page']
        );

        return $this->targetStudentsResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * List eligible students for center survey assignments.
     */
    public function centerTargetStudents(ListSurveyTargetStudentsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $filters = $request->filters();

        $paginator = $this->targetStudentService->paginate(
            actor: $admin,
            scopeType: SurveyScopeType::Center,
            centerId: (int) $center->id,
            status: $filters['status'],
            search: $filters['search'],
            perPage: $filters['per_page'],
            page: $filters['page']
        );

        return $this->targetStudentsResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * Create a system-scoped survey.
     */
    public function systemStore(StoreSurveyRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['scope_type'] = SurveyScopeType::System->value;
        $data['center_id'] = null;

        $survey = $this->surveyService->create($data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey created successfully',
            'data' => new SurveyResource($survey),
        ], 201);
    }

    /**
     * Create a center-scoped survey.
     */
    public function centerStore(StoreSurveyRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['scope_type'] = SurveyScopeType::Center->value;
        $data['center_id'] = (int) $center->id;

        $survey = $this->surveyService->create($data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey created successfully',
            'data' => new SurveyResource($survey),
        ], 201);
    }

    /**
     * Show a system-scoped survey.
     */
    public function systemShow(Request $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);

        $foundSurvey = $this->surveyService->find((int) $survey->id, $admin);

        if (! $foundSurvey instanceof Survey) {
            $this->notFound('Survey not found.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new SurveyResource($foundSurvey),
        ]);
    }

    /**
     * Show a center-scoped survey.
     */
    public function centerShow(Request $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);

        $foundSurvey = $this->surveyService->find((int) $survey->id, $admin);

        if (! $foundSurvey instanceof Survey) {
            $this->notFound('Survey not found.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new SurveyResource($foundSurvey),
        ]);
    }

    /**
     * Update a system-scoped survey.
     */
    public function systemUpdate(UpdateSurveyRequest $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $updated = $this->surveyService->update($survey, $data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey updated successfully',
            'data' => new SurveyResource($updated),
        ]);
    }

    /**
     * Update a center-scoped survey.
     */
    public function centerUpdate(UpdateSurveyRequest $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $updated = $this->surveyService->update($survey, $data, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey updated successfully',
            'data' => new SurveyResource($updated),
        ]);
    }

    /**
     * Delete a system-scoped survey.
     */
    public function systemDestroy(Request $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        $this->surveyService->delete($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Delete a center-scoped survey.
     */
    public function centerDestroy(Request $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);
        $this->surveyService->delete($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * Assign a system-scoped survey.
     */
    public function systemAssign(AssignSurveyRequest $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        /** @var array{assignments: array<int, array{type: string, id?: int}>} $data */
        $data = $request->validated();

        $warnings = $this->assignmentService->getPendingActiveWarnings($survey, $data['assignments']);
        $this->assignmentService->assignMultiple($survey, $data['assignments'], $admin);
        $survey->refresh()->load(['questions.options', 'center', 'creator', 'assignments']);

        return response()->json([
            'success' => true,
            'message' => 'Survey assigned successfully',
            'data' => new SurveyResource($survey),
            'warnings' => $warnings,
        ]);
    }

    /**
     * Assign a center-scoped survey.
     */
    public function centerAssign(AssignSurveyRequest $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);
        /** @var array{assignments: array<int, array{type: string, id?: int}>} $data */
        $data = $request->validated();

        $warnings = $this->assignmentService->getPendingActiveWarnings($survey, $data['assignments']);
        $this->assignmentService->assignMultiple($survey, $data['assignments'], $admin);
        $survey->refresh()->load(['questions.options', 'center', 'creator', 'assignments']);

        return response()->json([
            'success' => true,
            'message' => 'Survey assigned successfully',
            'data' => new SurveyResource($survey),
            'warnings' => $warnings,
        ]);
    }

    /**
     * Close a system-scoped survey.
     */
    public function systemClose(Request $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        $closed = $this->surveyService->close($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey closed successfully',
            'data' => new SurveyResource($closed->loadMissing(['questions.options', 'center', 'creator', 'assignments'])),
        ]);
    }

    /**
     * Close a center-scoped survey.
     */
    public function centerClose(Request $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);
        $closed = $this->surveyService->close($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey closed successfully',
            'data' => new SurveyResource($closed->loadMissing(['questions.options', 'center', 'creator', 'assignments'])),
        ]);
    }

    /**
     * Get analytics for a system-scoped survey.
     */
    public function systemAnalytics(Request $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        $analytics = $this->surveyService->getAnalytics($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new SurveyAnalyticsResource($analytics),
        ]);
    }

    /**
     * Get analytics for a center-scoped survey.
     */
    public function centerAnalytics(Request $request, Center $center, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);
        $analytics = $this->surveyService->getAnalytics($survey, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new SurveyAnalyticsResource($analytics),
        ]);
    }

    private function requireAdmin(Request $request): User
    {
        $admin = $request->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }

    private function assertSystemSurvey(Survey $survey): void
    {
        if ($survey->scope_type !== SurveyScopeType::System || $survey->center_id !== null) {
            $this->notFound('Survey not found.');
        }
    }

    private function assertCenterSurvey(Center $center, Survey $survey): void
    {
        if (
            $survey->scope_type !== SurveyScopeType::Center
            || ! is_numeric($survey->center_id)
            || (int) $survey->center_id !== (int) $center->id
        ) {
            $this->notFound('Survey not found.');
        }
    }

    /**
     * @param  array<int, Survey>  $surveys
     */
    private function surveyListResponse(
        array $surveys,
        int $page,
        int $perPage,
        int $total,
        int $lastPage
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => SurveyResource::collection($surveys),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * @param  array<int, User>  $students
     */
    private function targetStudentsResponse(
        array $students,
        int $page,
        int $perPage,
        int $total,
        int $lastPage
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully',
            'data' => SurveyTargetStudentResource::collection($students),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    private function notFound(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message,
            ],
        ], 404));
    }
}
