<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CenterType;
use App\Enums\SurveyScopeType;
use App\Filters\Admin\SurveyFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Surveys\AssignSurveyRequest;
use App\Http\Requests\Admin\Surveys\BulkCloseSurveysRequest;
use App\Http\Requests\Admin\Surveys\BulkDeleteSurveysRequest;
use App\Http\Requests\Admin\Surveys\BulkUpdateSurveyStatusRequest;
use App\Http\Requests\Admin\Surveys\ListSurveysRequest;
use App\Http\Requests\Admin\Surveys\ListSurveyTargetStudentsRequest;
use App\Http\Requests\Admin\Surveys\StoreSurveyRequest;
use App\Http\Requests\Admin\Surveys\UpdateSurveyRequest;
use App\Http\Requests\Admin\Surveys\UpdateSurveyStatusRequest;
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
            isMandatory: $requestFilters->isMandatory,
            type: $requestFilters->type,
            search: $requestFilters->search,
            startFrom: $requestFilters->startFrom,
            startTo: $requestFilters->startTo,
            endFrom: $requestFilters->endFrom,
            endTo: $requestFilters->endTo
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
        $this->assertBrandedCenter($center);
        $requestFilters = $request->filters();
        $filters = new SurveyFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            scopeType: SurveyScopeType::Center->value,
            centerId: (int) $center->id,
            isActive: $requestFilters->isActive,
            isMandatory: $requestFilters->isMandatory,
            type: $requestFilters->type,
            search: $requestFilters->search,
            startFrom: $requestFilters->startFrom,
            startTo: $requestFilters->startTo,
            endFrom: $requestFilters->endFrom,
            endTo: $requestFilters->endTo
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
        $this->assertBrandedCenter($center);
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

        try {
            $survey = $this->surveyService->create($data, $admin);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->invalidAssignment($invalidArgumentException->getMessage());
        }

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
        $this->assertBrandedCenter($center);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $data['scope_type'] = SurveyScopeType::Center->value;
        $data['center_id'] = (int) $center->id;

        try {
            $survey = $this->surveyService->create($data, $admin);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->invalidAssignment($invalidArgumentException->getMessage());
        }

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
     * Update system survey active status.
     */
    public function systemUpdateStatus(UpdateSurveyStatusRequest $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        /** @var array{is_active: bool} $data */
        $data = $request->validated();

        $updated = $this->surveyService->update($survey, ['is_active' => (bool) $data['is_active']], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey status updated successfully',
            'data' => new SurveyResource($updated),
        ]);
    }

    /**
     * Update center survey active status.
     */
    public function centerUpdateStatus(
        UpdateSurveyStatusRequest $request,
        Center $center,
        Survey $survey
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertCenterSurvey($center, $survey);
        /** @var array{is_active: bool} $data */
        $data = $request->validated();

        $updated = $this->surveyService->update($survey, ['is_active' => (bool) $data['is_active']], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Survey status updated successfully',
            'data' => new SurveyResource($updated),
        ]);
    }

    /**
     * Bulk update system surveys active status.
     */
    public function systemBulkUpdateStatus(BulkUpdateSurveyStatusRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{is_active: bool, survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];
        $statusValue = (bool) $data['is_active'];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (
                ! $surveyModel instanceof Survey
                || $surveyModel->scope_type !== SurveyScopeType::System
                || $surveyModel->center_id !== null
            ) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            if ((bool) $surveyModel->is_active === $statusValue) {
                $skipped[] = $surveyId;

                continue;
            }

            try {
                $updated[] = $this->surveyService->update($surveyModel, ['is_active' => $statusValue], $admin);
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey status update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => SurveyResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk update center surveys active status.
     */
    public function centerBulkUpdateStatus(
        BulkUpdateSurveyStatusRequest $request,
        Center $center
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertBrandedCenter($center);
        /** @var array{is_active: bool, survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];
        $statusValue = (bool) $data['is_active'];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (
                ! $surveyModel instanceof Survey
                || $surveyModel->scope_type !== SurveyScopeType::Center
                || ! is_numeric($surveyModel->center_id)
                || (int) $surveyModel->center_id !== (int) $center->id
            ) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            if ((bool) $surveyModel->is_active === $statusValue) {
                $skipped[] = $surveyId;

                continue;
            }

            try {
                $updated[] = $this->surveyService->update($surveyModel, ['is_active' => $statusValue], $admin);
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey status update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => SurveyResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk close system surveys.
     */
    public function systemBulkClose(BulkCloseSurveysRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $closed = [];
        $skipped = [];
        $failed = [];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (! $surveyModel instanceof Survey || ! $this->isSystemSurveyModel($surveyModel)) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            if (! $surveyModel->is_active) {
                $skipped[] = $surveyId;

                continue;
            }

            try {
                $closed[] = $this->surveyService->close($surveyModel, $admin);
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey close processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'closed' => count($closed),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'closed' => SurveyResource::collection($closed),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk close center surveys.
     */
    public function centerBulkClose(BulkCloseSurveysRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertBrandedCenter($center);
        /** @var array{survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $closed = [];
        $skipped = [];
        $failed = [];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (! $surveyModel instanceof Survey || ! $this->isCenterSurveyModelFor($surveyModel, $center)) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            if (! $surveyModel->is_active) {
                $skipped[] = $surveyId;

                continue;
            }

            try {
                $closed[] = $this->surveyService->close($surveyModel, $admin);
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey close processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'closed' => count($closed),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'closed' => SurveyResource::collection($closed),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk delete system surveys with safety checks.
     */
    public function systemBulkDestroy(BulkDeleteSurveysRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        /** @var array{survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->withCount('responses')
            ->get()
            ->keyBy('id');

        $deleted = [];
        $skipped = [];
        $failed = [];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (! $surveyModel instanceof Survey || ! $this->isSystemSurveyModel($surveyModel)) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            $skipReason = $this->bulkDeleteSkipReason($surveyModel);
            if ($skipReason !== null) {
                $skipped[] = [
                    'survey_id' => $surveyId,
                    'reason' => $skipReason,
                ];

                continue;
            }

            try {
                $this->surveyService->delete($surveyModel, $admin);
                $deleted[] = $surveyId;
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey delete processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'deleted' => count($deleted),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'deleted' => $deleted,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk delete center surveys with safety checks.
     */
    public function centerBulkDestroy(BulkDeleteSurveysRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertBrandedCenter($center);
        /** @var array{survey_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['survey_ids'])));
        $surveys = Survey::query()
            ->whereIn('id', $requestedIds)
            ->withCount('responses')
            ->get()
            ->keyBy('id');

        $deleted = [];
        $skipped = [];
        $failed = [];

        foreach ($requestedIds as $surveyId) {
            $surveyModel = $surveys->get($surveyId);
            if (! $surveyModel instanceof Survey || ! $this->isCenterSurveyModelFor($surveyModel, $center)) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => 'Survey not found.',
                ];

                continue;
            }

            $skipReason = $this->bulkDeleteSkipReason($surveyModel);
            if ($skipReason !== null) {
                $skipped[] = [
                    'survey_id' => $surveyId,
                    'reason' => $skipReason,
                ];

                continue;
            }

            try {
                $this->surveyService->delete($surveyModel, $admin);
                $deleted[] = $surveyId;
            } catch (\InvalidArgumentException $exception) {
                $failed[] = [
                    'survey_id' => $surveyId,
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk survey delete processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'deleted' => count($deleted),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'deleted' => $deleted,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Delete a system-scoped survey.
     */
    public function systemDestroy(Request $request, Survey $survey): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertSystemSurvey($survey);
        $this->assertSurveyCanBeDeleted($survey);
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
        $this->assertSurveyCanBeDeleted($survey);
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

        try {
            $warnings = $this->assignmentService->getPendingActiveWarnings($survey, $data['assignments']);
            $this->assignmentService->assignMultiple($survey, $data['assignments'], $admin);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->invalidAssignment($invalidArgumentException->getMessage());
        }

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

        try {
            $warnings = $this->assignmentService->getPendingActiveWarnings($survey, $data['assignments']);
            $this->assignmentService->assignMultiple($survey, $data['assignments'], $admin);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->invalidAssignment($invalidArgumentException->getMessage());
        }

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
        $this->assertBrandedCenter($center);

        if (
            $survey->scope_type !== SurveyScopeType::Center
            || ! is_numeric($survey->center_id)
            || (int) $survey->center_id !== (int) $center->id
        ) {
            $this->notFound('Survey not found.');
        }
    }

    private function isSystemSurveyModel(Survey $survey): bool
    {
        return $survey->scope_type === SurveyScopeType::System && $survey->center_id === null;
    }

    private function isCenterSurveyModelFor(Survey $survey, Center $center): bool
    {
        return $survey->scope_type === SurveyScopeType::Center
            && is_numeric($survey->center_id)
            && (int) $survey->center_id === (int) $center->id;
    }

    private function bulkDeleteSkipReason(Survey $survey): ?string
    {
        $responsesCount = is_numeric($survey->responses_count ?? null)
            ? (int) $survey->responses_count
            : 0;

        if ($responsesCount > 0) {
            return 'Survey with responses cannot be deleted.';
        }

        if ($survey->is_active) {
            return 'Active survey cannot be deleted. Close it first.';
        }

        return null;
    }

    private function assertSurveyCanBeDeleted(Survey $survey): void
    {
        $survey->loadCount('responses');
        $skipReason = $this->bulkDeleteSkipReason($survey);
        if ($skipReason !== null) {
            $this->deleteNotAllowed($skipReason);
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

    private function invalidAssignment(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message !== '' ? $message : 'Validation failed.',
            ],
        ], 422));
    }

    private function deleteNotAllowed(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message !== '' ? $message : 'Validation failed.',
            ],
        ], 422));
    }

    private function assertBrandedCenter(Center $center): void
    {
        if ($center->type !== CenterType::Branded) {
            $this->invalidAssignment('Center surveys are allowed only for branded centers.');
        }
    }
}
