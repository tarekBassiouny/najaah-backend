<?php

declare(strict_types=1);

namespace App\Services\Surveys;

use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScopeType;
use App\Filters\Admin\SurveyFilters;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Surveys\Contracts\SurveyAssignmentServiceInterface;
use App\Services\Surveys\Contracts\SurveyServiceInterface;
use App\Support\AuditActions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SurveyService implements SurveyServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService,
        private readonly SurveyAssignmentServiceInterface $assignmentService
    ) {}

    /** @return LengthAwarePaginator<Survey> */
    public function paginate(SurveyFilters $filters, User $actor): LengthAwarePaginator
    {
        $query = Survey::query()
            ->with(['center', 'creator', 'questions.options'])
            ->withCount('responses')
            ->orderByDesc('created_at');

        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            if ($filters->scopeType !== null) {
                $query->where('scope_type', $filters->scopeType);
            }

            if ($filters->centerId !== null) {
                // Show surveys for the specific center + system-wide surveys
                $query->forCenterWithSystem($filters->centerId);
            }
        } else {
            $centerId = $actor->center_id;
            $this->centerScopeService->assertAdminCenterId($actor, $centerId);
            $query->where('scope_type', SurveyScopeType::Center)
                ->where('center_id', $centerId);
        }

        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Survey
    {
        return DB::transaction(function () use ($data, $actor): Survey {
            $scopeType = SurveyScopeType::from((int) $data['scope_type']);

            if ($scopeType === SurveyScopeType::System) {
                if (! $this->centerScopeService->isSystemSuperAdmin($actor)) {
                    throw new \InvalidArgumentException('Only super admins can create system surveys');
                }

                $data['center_id'] = null;
            } else {
                $centerId = $data['center_id'] ?? $this->centerScopeService->resolveAdminCenterId($actor);
                $this->centerScopeService->assertAdminCenterId($actor, $centerId);
                $data['center_id'] = $centerId;
            }

            $data['created_by'] = $actor->id;
            $data['is_active'] = $data['is_active'] ?? false;

            $questions = $data['questions'] ?? [];
            $assignments = $data['assignments'] ?? [];
            unset($data['questions'], $data['assignments']);

            $survey = Survey::create($data);

            foreach ($questions as $index => $questionData) {
                $this->createQuestion($survey, $questionData, $index);
            }

            // Handle assignments if provided
            if (! empty($assignments)) {
                $this->assignmentService->assignMultiple($survey, $assignments, $actor);
            }

            $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_CREATED, [
                'scope_type' => $scopeType->name,
                'center_id' => $survey->center_id,
                'assignments_count' => count($assignments),
            ]);

            return $survey->fresh(['questions.options', 'center', 'creator', 'assignments']) ?? $survey;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Survey $survey, array $data, User $actor): Survey
    {
        return DB::transaction(function () use ($survey, $data, $actor): Survey {
            $this->assertCanManageSurvey($survey, $actor);

            $questions = $data['questions'] ?? null;
            unset($data['questions']);

            $survey->update($data);

            if ($questions !== null) {
                $survey->questions()->delete();

                foreach ($questions as $index => $questionData) {
                    $this->createQuestion($survey, $questionData, $index);
                }
            }

            $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_UPDATED, [
                'updated_fields' => array_keys($data),
            ]);

            return $survey->fresh(['questions.options', 'center', 'creator']) ?? $survey;
        });
    }

    public function delete(Survey $survey, User $actor): void
    {
        $this->assertCanManageSurvey($survey, $actor);

        $survey->delete();

        $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_DELETED);
    }

    public function find(int $id, User $actor): ?Survey
    {
        $survey = Survey::with(['questions.options', 'center', 'creator', 'assignments'])
            ->withCount('responses')
            ->find($id);

        if ($survey !== null) {
            $this->assertCanManageSurvey($survey, $actor);
        }

        return $survey;
    }

    public function close(Survey $survey, User $actor): Survey
    {
        $this->assertCanManageSurvey($survey, $actor);

        $survey->update(['is_active' => false]);

        $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_CLOSED);

        return $survey->fresh() ?? $survey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(Survey $survey, User $actor): array
    {
        $this->assertCanManageSurvey($survey, $actor);

        $survey->load(['questions.options', 'responses.answers']);

        $totalResponses = $survey->responses->count();
        $questionStats = [];

        foreach ($survey->questions as $question) {
            $answers = $survey->responses->flatMap(fn ($r) => $r->answers)
                ->where('survey_question_id', $question->id);

            $stats = [
                'question_id' => $question->id,
                'question' => $question->question_translations,
                'type' => $question->type->name,
                'total_answers' => $answers->count(),
                'distribution' => [],
            ];

            switch ($question->type) {
                case SurveyQuestionType::SingleChoice:
                case SurveyQuestionType::MultipleChoice:
                    $optionCounts = [];
                    foreach ($question->options as $option) {
                        $optionCounts[$option->id] = [
                            'option' => $option->option_translations,
                            'count' => 0,
                        ];
                    }

                    foreach ($answers as $answer) {
                        $selectedIds = $answer->answer_json ?? [$answer->answer_number];
                        foreach ((array) $selectedIds as $optionId) {
                            if (isset($optionCounts[$optionId])) {
                                $optionCounts[$optionId]['count']++;
                            }
                        }
                    }

                    $stats['distribution'] = array_values($optionCounts);
                    break;

                case SurveyQuestionType::Rating:
                    $ratings = $answers->pluck('answer_number')->filter()->values();
                    $stats['average'] = $ratings->isNotEmpty() ? round($ratings->avg(), 2) : null;
                    $stats['distribution'] = $ratings->countBy()->sortKeys()->all();
                    break;

                case SurveyQuestionType::YesNo:
                    $stats['distribution'] = [
                        'yes' => $answers->where('answer_number', 1)->count(),
                        'no' => $answers->where('answer_number', 0)->count(),
                    ];
                    break;

                case SurveyQuestionType::Text:
                    $stats['sample_answers'] = $answers->take(10)->pluck('answer_text')->filter()->values()->all();
                    break;
            }

            $questionStats[] = $stats;
        }

        return [
            'survey_id' => $survey->id,
            'total_responses' => $totalResponses,
            'completion_rate' => $totalResponses > 0 ? 100 : 0,
            'questions' => $questionStats,
        ];
    }

    /**
     * @param  array<string, mixed>  $questionData
     */
    private function createQuestion(Survey $survey, array $questionData, int $index): SurveyQuestion
    {
        $options = $questionData['options'] ?? [];
        unset($questionData['options']);

        $questionData['survey_id'] = $survey->id;
        $questionData['order_index'] = $questionData['order_index'] ?? $index;

        $question = SurveyQuestion::create($questionData);

        foreach ($options as $optionIndex => $optionData) {
            $optionData['survey_question_id'] = $question->id;
            $optionData['order_index'] = $optionData['order_index'] ?? $optionIndex;
            SurveyQuestionOption::create($optionData);
        }

        return $question;
    }

    private function assertCanManageSurvey(Survey $survey, User $actor): void
    {
        if ($survey->scope_type === SurveyScopeType::System) {
            if (! $this->centerScopeService->isSystemSuperAdmin($actor)) {
                throw new \InvalidArgumentException('Only super admins can manage system surveys');
            }
        } else {
            $this->centerScopeService->assertAdminSameCenter($actor, $survey);
        }
    }
}
