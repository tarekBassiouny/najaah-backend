<?php

declare(strict_types=1);

namespace App\Services\Surveys;

use App\Enums\CenterType;
use App\Enums\SurveyAssignableType;
use App\Enums\SurveyScopeType;
use App\Models\Center;
use App\Models\Course;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\User;
use App\Models\Video;
use App\Services\Audit\AuditLogService;
use App\Services\Surveys\Contracts\SurveyAssignmentServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyAssignmentService implements SurveyAssignmentServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function validateAssignment(Survey $survey, SurveyAssignableType $type, ?int $id): bool
    {
        // "All" type is always valid - it assigns to all students based on survey scope
        if ($type === SurveyAssignableType::All) {
            return true;
        }

        if ($id === null) {
            return false;
        }

        $model = $this->findAssignable($type, $id);

        if ($model === null) {
            return false;
        }

        if ($survey->scope_type === SurveyScopeType::System) {
            return match ($type) {
                SurveyAssignableType::Center => $this->isValidCenterForSystemScope($model),
                SurveyAssignableType::User => $this->isValidStudentForSystemScope($model),
                SurveyAssignableType::Course => $this->isValidCourseForSystemScope($model),
                default => false,
            };
        }

        // Center-scoped surveys support only user/course/video/all.
        return match ($type) {
            SurveyAssignableType::User => $this->isValidStudentForCenterScope($model, $survey),
            SurveyAssignableType::Course => $model instanceof Course
                && is_numeric($survey->center_id)
                && (int) $model->center_id === (int) $survey->center_id,
            SurveyAssignableType::Video => $this->isValidVideoForCenterScope($model, $survey),
            default => false,
        };
    }

    /**
     * @param  array<array{type: string, id?: int|string}>  $assignments
     * @return array<int, array{type: string, id: int|null, conflicting_count: int, conflicting_survey_ids: array<int>}>
     */
    public function getPendingActiveWarnings(Survey $survey, array $assignments): array
    {
        $warnings = [];
        $seen = [];

        foreach ($assignments as $assignment) {
            $type = SurveyAssignableType::from($assignment['type']);
            $id = $this->extractAssignmentId($assignment);

            // Skip "All" type for conflict warnings (handled separately)
            if ($type === SurveyAssignableType::All) {
                continue;
            }

            if ($id === null) {
                continue;
            }

            $key = $type->value.':'.$id;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            if (! $this->validateAssignment($survey, $type, $id)) {
                continue;
            }

            $conflictQuery = Survey::query()
                ->where('id', '!=', $survey->id)
                ->available()
                ->whereHas('assignments', function ($query) use ($type, $id): void {
                    $query->where('assignable_type', $type->value)
                        ->where('assignable_id', $id);
                });

            $conflictingCount = (clone $conflictQuery)->count();

            if ($conflictingCount === 0) {
                continue;
            }

            $conflictingSurveyIds = $conflictQuery
                ->orderByDesc('is_mandatory')
                ->orderBy('end_at')
                ->orderByDesc('created_at')
                ->limit(3)
                ->pluck('id')
                ->map(static fn ($surveyId): int => (int) $surveyId)
                ->all();

            $warnings[] = [
                'type' => $type->value,
                'id' => $id,
                'conflicting_count' => $conflictingCount,
                'conflicting_survey_ids' => $conflictingSurveyIds,
            ];
        }

        return $warnings;
    }

    /**
     * @param  array<array{type: string, id?: int|string}>  $assignments
     */
    public function assignMultiple(Survey $survey, array $assignments, User $actor): void
    {
        DB::transaction(function () use ($survey, $assignments, $actor): void {
            $validAssignments = [];
            $hasAllAssignment = false;

            foreach ($assignments as $assignment) {
                $type = SurveyAssignableType::from($assignment['type']);

                // Handle "All" assignment type separately
                if ($type === SurveyAssignableType::All) {
                    $hasAllAssignment = true;

                    continue;
                }

                $id = $this->extractAssignmentId($assignment);

                if ($id === null) {
                    throw new \InvalidArgumentException(
                        sprintf('Assignment ID is required for type: %s', $type->value)
                    );
                }

                if (! $this->validateAssignment($survey, $type, $id)) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid assignment: %s with ID %d cannot be assigned to this survey', $type->value, $id)
                    );
                }

                $existing = SurveyAssignment::where('survey_id', $survey->id)
                    ->where('assignable_type', $type)
                    ->where('assignable_id', $id)
                    ->first();

                if ($existing === null) {
                    $validAssignments[] = [
                        'survey_id' => $survey->id,
                        'assignable_type' => $type,
                        'assignable_id' => $id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($validAssignments)) {
                SurveyAssignment::insert($validAssignments);
            }

            // Process "All" assignment after individual assignments
            if ($hasAllAssignment) {
                $this->assignAll($survey);
            }

            $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_ASSIGNED, [
                'assignments' => $assignments,
            ]);
        });
    }

    /**
     * Assign survey to all eligible students based on survey scope.
     * - CENTER scoped: All students in the survey's center
     * - SYSTEM scoped: All students in unbranded centers + students without center
     */
    public function assignAll(Survey $survey): int
    {
        $query = User::query()->where('is_student', true);

        if ($survey->isCenterScoped()) {
            // CENTER scoped: only students in this center
            $query->where('center_id', $survey->center_id);
        } else {
            // SYSTEM scoped: students without center OR in unbranded centers
            $query->where(function ($q): void {
                $q->whereNull('center_id')
                    ->orWhereHas('center', function ($centerQuery): void {
                        $centerQuery->where('type', CenterType::Unbranded->value);
                    });
            });
        }

        $studentIds = $query->pluck('id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $now = now();
        $assignments = [];

        foreach ($studentIds as $studentId) {
            // Check if assignment already exists
            $existing = SurveyAssignment::where('survey_id', $survey->id)
                ->where('assignable_type', SurveyAssignableType::All)
                ->where('assignable_id', $studentId)
                ->exists();

            if (! $existing) {
                $assignments[] = [
                    'survey_id' => $survey->id,
                    'assignable_type' => SurveyAssignableType::All,
                    'assignable_id' => $studentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($assignments)) {
            // Chunk insert to avoid memory issues with large datasets
            foreach (array_chunk($assignments, 500) as $chunk) {
                SurveyAssignment::insert($chunk);
            }
        }

        return count($assignments);
    }

    public function removeAssignment(Survey $survey, SurveyAssignableType $type, int $id, User $actor): void
    {
        SurveyAssignment::where('survey_id', $survey->id)
            ->where('assignable_type', $type)
            ->where('assignable_id', $id)
            ->delete();

        $this->auditLogService->log($actor, $survey, AuditActions::SURVEY_ASSIGNMENT_REMOVED, [
            'type' => $type->value,
            'id' => $id,
        ]);
    }

    /**
     * @return Collection<int, SurveyAssignment>
     */
    public function getAssignments(Survey $survey): Collection
    {
        return $survey->assignments()->get();
    }

    private function findAssignable(SurveyAssignableType $type, int $id): Center|Course|Video|User|null
    {
        return match ($type) {
            SurveyAssignableType::Center => Center::find($id),
            SurveyAssignableType::Course => Course::find($id),
            SurveyAssignableType::Video => Video::find($id),
            SurveyAssignableType::User => User::find($id),
            SurveyAssignableType::All => null,
        };
    }

    /**
     * @param  array{type: string, id?: int|string|null}  $assignment
     */
    private function extractAssignmentId(array $assignment): ?int
    {
        $id = $assignment['id'] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && filter_var($id, FILTER_VALIDATE_INT) !== false) {
            return (int) $id;
        }

        throw new \InvalidArgumentException('Assignment ID must be an integer.');
    }

    private function isValidCenterForSystemScope(Center|Course|Video|User $model): bool
    {
        return $model instanceof Center && $model->type === CenterType::Unbranded;
    }

    private function isValidStudentForSystemScope(object $model): bool
    {
        if (! $model instanceof User || ! $model->is_student) {
            return false;
        }

        if ($model->center_id === null) {
            return true;
        }

        if (! is_numeric($model->center_id)) {
            return false;
        }

        $center = $model->center;

        return $center instanceof Center && $center->type === CenterType::Unbranded;
    }

    private function isValidCourseForSystemScope(object $model): bool
    {
        if (! $model instanceof Course) {
            return false;
        }

        $center = $model->center;

        return $center instanceof Center && $center->type === CenterType::Unbranded;
    }

    private function isValidStudentForCenterScope(object $model, Survey $survey): bool
    {
        if (! $model instanceof User || ! $model->is_student || ! is_numeric($model->center_id)) {
            return false;
        }

        return (int) $model->center_id === (int) $survey->center_id;
    }

    private function isValidVideoForCenterScope(object $model, Survey $survey): bool
    {
        if (! $model instanceof Video || ! is_numeric($survey->center_id)) {
            return false;
        }

        return (int) $model->center_id === (int) $survey->center_id;
    }
}
