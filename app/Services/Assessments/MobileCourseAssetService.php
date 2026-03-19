<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIContentTargetType;
use App\Enums\LearningAssetProgressStatus;
use App\Enums\LearningAssetType;
use App\Exceptions\DomainException;
use App\Http\Resources\Mobile\Assignment\AssignmentSubmissionResource;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAsset;
use App\Models\LearningAssetProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use App\Services\Assessments\Contracts\MobileCourseAssetServiceInterface;
use App\Services\Assessments\Contracts\QuizAttemptServiceInterface;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use Illuminate\Support\Str;

final class MobileCourseAssetService implements MobileCourseAssetServiceInterface
{
    public function __construct(
        private QuizServiceInterface $quizService,
        private QuizAttemptServiceInterface $quizAttemptService,
        private AssignmentServiceInterface $assignmentService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForCourse(User $student, int $centerId, Course $course, ?AIContentTargetType $type = null): array
    {
        $this->assertCourseAccess($student, $centerId, $course);

        $assets = [];

        if ($type === null || $type === AIContentTargetType::Quiz) {
            $quizzes = Quiz::query()
                ->where('course_id', $course->id)
                ->where('center_id', $centerId)
                ->where('is_active', true)
                ->withCount(['questions as active_questions_count' => fn ($query) => $query->active()])
                ->orderBy('order_index')
                ->get();

            foreach ($quizzes as $quiz) {
                $assets[] = $this->normalizeQuizList($quiz, $student);
            }
        }

        if ($type === null || $type === AIContentTargetType::Assignment) {
            $assignments = Assignment::query()
                ->where('course_id', $course->id)
                ->where('center_id', $centerId)
                ->where('is_active', true)
                ->with([
                    'submissions' => fn ($query) => $query
                        ->where('user_id', $student->id)
                        ->latest('id'),
                ])
                ->orderBy('order_index')
                ->get();

            foreach ($assignments as $assignment) {
                $assets[] = $this->normalizeAssignmentList($assignment, $student);
            }
        }

        if ($type === null || $this->isLearningAssetType($type)) {
            $query = LearningAsset::query()
                ->where('center_id', $centerId)
                ->where('course_id', $course->id)
                ->published()
                ->orderByDesc('published_at')
                ->orderByDesc('id');

            if ($type !== null) {
                $query->where('asset_type', $this->toLearningAssetType($type));
            }

            $learningAssets = $query->get();
            $progressByAssetId = $this->resolveLearningAssetProgressMap($student, $learningAssets);

            foreach ($learningAssets as $asset) {
                $assets[] = $this->normalizeLearningAssetList($asset, $progressByAssetId[$asset->id] ?? null);
            }
        }

        usort($assets, $this->compareAssets(...));

        return $assets;
    }

    /**
     * @return array<string, mixed>
     */
    public function show(User $student, int $centerId, AIContentTargetType $type, int $id): array
    {
        return match ($type) {
            AIContentTargetType::Quiz => $this->showQuiz($student, $centerId, $id),
            AIContentTargetType::Assignment => $this->showAssignment($student, $centerId, $id),
            AIContentTargetType::Summary,
            AIContentTargetType::Flashcards,
            AIContentTargetType::InteractiveActivity => $this->showLearningAsset($student, $centerId, $type, $id),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function trackProgress(User $student, int $centerId, AIContentTargetType $type, int $id, array $payload): array
    {
        if (! $this->isLearningAssetType($type)) {
            throw new DomainException(
                'Progress tracking is only available for summary, flashcards, and interactive activities.',
                'UNSUPPORTED_PROGRESS_TRACKING',
                400
            );
        }

        $asset = $this->resolveLearningAsset($centerId, $type, $id);
        $enrollment = $this->findEnrollment($student, $centerId, $asset->course_id);

        if (! $enrollment instanceof Enrollment) {
            throw new DomainException('You are not enrolled in this course.', 'NOT_ENROLLED', 403);
        }

        $progress = LearningAssetProgress::query()->firstOrNew([
            'learning_asset_id' => $asset->id,
            'user_id' => $student->id,
        ]);

        if (! $progress->exists) {
            $progress->center_id = $centerId;
            $progress->course_id = $asset->course_id;
            $progress->enrollment_id = $enrollment->id;
            $progress->started_at = now();
        }

        $progress->status = LearningAssetProgressStatus::InProgress;
        $progress->progress_percent = max(0, min(100, (int) ($payload['progress_percent'] ?? $progress->progress_percent ?? 0)));
        $progress->state = array_key_exists('state', $payload) ? $payload['state'] : $progress->state;
        $progress->last_interacted_at = now();

        $markCompleted = (bool) ($payload['completed'] ?? false) || $progress->progress_percent >= 100;

        if ($markCompleted) {
            $progress->status = LearningAssetProgressStatus::Completed;
            $progress->progress_percent = 100;
            $progress->completed_at ??= now();
        } else {
            $progress->completed_at = null;
        }

        if ($progress->started_at === null) {
            $progress->started_at = now();
        }

        $progress->save();

        return $this->normalizeLearningAssetProgressDetail($progress);
    }

    /**
     * @return array<string, mixed>
     */
    private function showQuiz(User $student, int $centerId, int $id): array
    {
        $quiz = Quiz::query()
            ->withCount(['questions as active_questions_count' => fn ($query) => $query->active()])
            ->find($id);

        if (! $quiz instanceof Quiz || $quiz->center_id !== $centerId) {
            throw new DomainException('Quiz not found.', 'NOT_FOUND', 404);
        }

        $this->assertEnrollment($student, $centerId, $quiz->course_id);

        if (! $this->quizService->isAvailable($quiz, $student)) {
            throw new DomainException('This quiz is not currently available.', 'NOT_AVAILABLE', 400);
        }

        return $this->normalizeQuizDetail($quiz, $student);
    }

    /**
     * @return array<string, mixed>
     */
    private function showAssignment(User $student, int $centerId, int $id): array
    {
        $assignment = Assignment::query()
            ->with([
                'submissions' => fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->with('files')
                    ->latest('id'),
            ])
            ->find($id);

        if (! $assignment instanceof Assignment || $assignment->center_id !== $centerId) {
            throw new DomainException('Assignment not found.', 'NOT_FOUND', 404);
        }

        $this->assertEnrollment($student, $centerId, $assignment->course_id);

        if (! $this->assignmentService->isAvailable($assignment, $student)) {
            throw new DomainException('This assignment is not currently available.', 'NOT_AVAILABLE', 400);
        }

        return $this->normalizeAssignmentDetail($assignment, $student);
    }

    /**
     * @return array<string, mixed>
     */
    private function showLearningAsset(User $student, int $centerId, AIContentTargetType $type, int $id): array
    {
        $asset = $this->resolveLearningAsset($centerId, $type, $id);

        $this->assertEnrollment($student, $centerId, $asset->course_id);

        return $this->normalizeLearningAssetDetail(
            $asset,
            $this->resolveLearningAssetProgress($student, $asset)
        );
    }

    private function assertCourseAccess(User $student, int $centerId, Course $course): void
    {
        if ((int) $course->center_id !== $centerId) {
            throw new DomainException('Course not found.', 'NOT_FOUND', 404);
        }

        $this->assertEnrollment($student, $centerId, $course->id);
    }

    private function assertEnrollment(User $student, int $centerId, int $courseId): void
    {
        $enrolled = $this->findEnrollment($student, $centerId, $courseId);

        if (! $enrolled instanceof Enrollment) {
            throw new DomainException('You are not enrolled in this course.', 'NOT_ENROLLED', 403);
        }
    }

    private function findEnrollment(User $student, int $centerId, int $courseId): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $student->id)
            ->where('center_id', $centerId)
            ->where('course_id', $courseId)
            ->active()
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeQuizList(Quiz $quiz, User $student): array
    {
        return [
            'id' => $quiz->id,
            'type' => AIContentTargetType::Quiz->value,
            'title' => $quiz->translate('title'),
            'description' => $quiz->translate('description'),
            'attachable_type' => $quiz->attachable_type,
            'attachable_id' => $quiz->attachable_id,
            'is_available' => $this->quizService->isAvailable($quiz, $student),
            'is_required' => $quiz->is_required,
            'order_index' => $quiz->order_index,
            'published_at' => null,
            'meta' => [
                'questions_count' => (int) ($quiz->active_questions_count ?? 0),
                'remaining_attempts' => $this->quizService->getRemainingAttempts($quiz, $student),
                'best_score' => $this->quizAttemptService->calculateFinalScore($quiz, $student),
                'can_attempt' => $this->quizService->canAttempt($quiz, $student),
                'time_limit_minutes' => $quiz->time_limit_minutes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeQuizDetail(Quiz $quiz, User $student): array
    {
        $list = $this->normalizeQuizList($quiz, $student);

        $list['payload'] = [
            'passing_score' => (float) $quiz->passing_score,
            'max_attempts' => $quiz->max_attempts,
            'time_limit_minutes' => $quiz->time_limit_minutes,
            'shuffle_questions' => $quiz->shuffle_questions,
            'shuffle_answers' => $quiz->shuffle_answers,
            'show_correct_answers' => $quiz->show_correct_answers,
            'show_score_immediately' => $quiz->show_score_immediately,
            'available_from' => $quiz->available_from?->toIso8601String(),
            'available_until' => $quiz->available_until?->toIso8601String(),
            'remaining_attempts' => $this->quizService->getRemainingAttempts($quiz, $student),
            'best_score' => $this->quizAttemptService->calculateFinalScore($quiz, $student),
            'can_attempt' => $this->quizService->canAttempt($quiz, $student),
            'total_questions' => (int) ($quiz->active_questions_count ?? $quiz->questions()->active()->count()),
            'total_points' => $quiz->getTotalPoints(),
            'attempts' => $this->buildQuizAttemptSummary($quiz, $student),
            'stats' => $this->buildQuizAttemptStats($quiz, $student),
        ];

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAssignmentList(Assignment $assignment, User $student): array
    {
        $submission = $assignment->submissions->first();

        return [
            'id' => $assignment->id,
            'type' => AIContentTargetType::Assignment->value,
            'title' => $assignment->translate('title'),
            'description' => $assignment->translate('description'),
            'attachable_type' => $assignment->attachable_type,
            'attachable_id' => $assignment->attachable_id,
            'is_available' => $this->assignmentService->isAvailable($assignment, $student),
            'is_required' => $assignment->is_required,
            'order_index' => $assignment->order_index,
            'published_at' => null,
            'meta' => [
                'submission_types' => $assignment->submission_types,
                'can_submit' => $this->assignmentService->canSubmit($assignment, $student),
                'due_date' => $assignment->due_date?->toIso8601String(),
                'submission_status' => $submission?->status->value,
                'submission_status_label' => $submission?->status->label(),
                'score' => $submission?->score !== null ? (float) $submission->score : null,
                'passed' => $submission?->passed,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAssignmentDetail(Assignment $assignment, User $student): array
    {
        $list = $this->normalizeAssignmentList($assignment, $student);
        $submission = $assignment->submissions->first();

        $list['payload'] = [
            'submission_types' => $assignment->submission_types,
            'allowed_file_types' => $assignment->allowed_file_types,
            'max_file_size_mb' => $assignment->max_file_size_mb,
            'max_files' => $assignment->max_files,
            'is_group_assignment' => $assignment->is_group_assignment,
            'max_group_size' => $assignment->max_group_size,
            'max_points' => (float) $assignment->max_points,
            'passing_score' => (float) $assignment->passing_score,
            'due_date' => $assignment->due_date?->toIso8601String(),
            'is_past_due' => $assignment->isPastDue(),
            'is_late' => $this->assignmentService->isLate($assignment),
            'late_submission_allowed' => $assignment->late_submission_allowed,
            'late_penalty_percent' => (float) $assignment->late_penalty_percent,
            'available_from' => $assignment->available_from?->toIso8601String(),
            'available_until' => $assignment->available_until?->toIso8601String(),
            'can_submit' => $this->assignmentService->canSubmit($assignment, $student),
            'my_submission' => $submission ? (new AssignmentSubmissionResource($submission))->toArray(request()) : null,
        ];

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearningAssetList(LearningAsset $asset, ?LearningAssetProgress $progress = null): array
    {
        $content = $asset->translate('content');

        $meta = match ($asset->asset_type) {
            LearningAssetType::Summary => [
                'content_preview' => is_string($content) ? Str::limit($content, 140) : null,
            ],
            LearningAssetType::Flashcards => [
                'cards_count' => $this->resolveFlashcardCount($asset),
            ],
            LearningAssetType::InteractiveActivity => [
                'activity_kind' => data_get($asset->payload, 'activity_kind')
                    ?? data_get($asset->payload, 'kind'),
            ],
        };

        $meta['progress'] = $this->normalizeLearningAssetProgressSummary($progress);

        return [
            'id' => $asset->id,
            'type' => $asset->asset_type->value,
            'title' => $asset->translate('title'),
            'description' => is_string($content) && $content !== '' ? Str::limit($content, 140) : null,
            'attachable_type' => $asset->attachable_type,
            'attachable_id' => $asset->attachable_id,
            'is_available' => true,
            'is_required' => false,
            'order_index' => null,
            'published_at' => $asset->published_at?->toIso8601String(),
            'meta' => $meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearningAssetDetail(LearningAsset $asset, ?LearningAssetProgress $progress = null): array
    {
        $list = $this->normalizeLearningAssetList($asset, $progress);

        $list['payload'] = [
            'content' => $asset->translate('content'),
            'payload' => $asset->payload,
            'progress' => $this->normalizeLearningAssetProgressDetail($progress),
        ];

        return $list;
    }

    private function resolveLearningAsset(int $centerId, AIContentTargetType $type, int $id): LearningAsset
    {
        $asset = LearningAsset::query()->find($id);

        if (
            ! $asset instanceof LearningAsset
            || $asset->center_id !== $centerId
            || $asset->asset_type !== $this->toLearningAssetType($type)
            || ! $asset->is_active
            || $asset->status !== \App\Enums\LearningAssetStatus::Published
        ) {
            throw new DomainException('Learning asset not found.', 'NOT_FOUND', 404);
        }

        return $asset;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, LearningAsset>  $assets
     * @return array<int, LearningAssetProgress>
     */
    private function resolveLearningAssetProgressMap(User $student, \Illuminate\Database\Eloquent\Collection $assets): array
    {
        $assetIds = $assets->pluck('id')->all();

        if ($assetIds === []) {
            return [];
        }

        /** @var array<int, LearningAssetProgress> $progressByAssetId */
        $progressByAssetId = LearningAssetProgress::query()
            ->where('user_id', $student->id)
            ->whereIn('learning_asset_id', $assetIds)
            ->get()
            ->keyBy('learning_asset_id')
            ->all();

        return $progressByAssetId;
    }

    private function resolveLearningAssetProgress(User $student, LearningAsset $asset): ?LearningAssetProgress
    {
        return LearningAssetProgress::query()
            ->where('learning_asset_id', $asset->id)
            ->where('user_id', $student->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearningAssetProgressSummary(?LearningAssetProgress $progress): array
    {
        if (! $progress instanceof LearningAssetProgress) {
            return [
                'status' => 'not_started',
                'status_label' => 'Not Started',
                'progress_percent' => 0,
                'is_completed' => false,
                'last_interacted_at' => null,
                'completed_at' => null,
            ];
        }

        return [
            'status' => $progress->status->apiValue(),
            'status_label' => $progress->status->label(),
            'progress_percent' => $progress->progress_percent,
            'is_completed' => $progress->status->isCompleted(),
            'last_interacted_at' => $progress->last_interacted_at?->toIso8601String(),
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearningAssetProgressDetail(?LearningAssetProgress $progress): array
    {
        $summary = $this->normalizeLearningAssetProgressSummary($progress);
        $summary['state'] = $progress?->state;
        $summary['started_at'] = $progress?->started_at?->toIso8601String();

        return $summary;
    }

    private function resolveFlashcardCount(LearningAsset $asset): int
    {
        $cards = data_get($asset->payload, 'cards');

        if (is_array($cards)) {
            return count($cards);
        }

        return (int) (data_get($asset->payload, 'card_count') ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildQuizAttemptSummary(Quiz $quiz, User $student): array
    {
        $attempts = $this->quizAttemptService->getAttempts($quiz, $student);
        $totalQuestions = $quiz->questions()->active()->count();

        return $attempts->map(fn (QuizAttempt $attempt): array => [
            'id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status->value,
            'status_label' => $attempt->status->label(),
            'score' => $attempt->score !== null ? (float) $attempt->score : null,
            'passed' => $attempt->passed,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'answered_questions' => (int) ($attempt->answers_count ?? 0),
            'total_questions' => $totalQuestions,
            'can_resume' => $attempt->isInProgress(),
        ])->values()->all();
    }

    /**
     * @return array<string, float|int|null>
     */
    private function buildQuizAttemptStats(Quiz $quiz, User $student): array
    {
        $attempts = $this->quizAttemptService->getAttempts($quiz, $student);
        $gradedAttempts = $attempts->filter(static fn (QuizAttempt $attempt): bool => is_numeric($attempt->score));
        $completedAttempts = $attempts->filter(static fn (QuizAttempt $attempt): bool => $attempt->status->isCompleted());
        $failedCompletedAttempts = $completedAttempts->filter(static fn (QuizAttempt $attempt): bool => $attempt->passed === false);

        return [
            'lowest_score' => $gradedAttempts->isEmpty() ? null : (float) $gradedAttempts->min('score'),
            'average_score' => $gradedAttempts->isEmpty() ? null : round((float) $gradedAttempts->avg('score'), 2),
            'highest_score' => $gradedAttempts->isEmpty() ? null : (float) $gradedAttempts->max('score'),
            'opened_count' => $attempts->count(),
            'completed_count' => $completedAttempts->count(),
            'failed_count' => $failedCompletedAttempts->count(),
        ];
    }

    private function isLearningAssetType(?AIContentTargetType $type): bool
    {
        return in_array($type, [
            AIContentTargetType::Summary,
            AIContentTargetType::Flashcards,
            AIContentTargetType::InteractiveActivity,
        ], true);
    }

    private function toLearningAssetType(AIContentTargetType $type): LearningAssetType
    {
        return match ($type) {
            AIContentTargetType::Summary => LearningAssetType::Summary,
            AIContentTargetType::Flashcards => LearningAssetType::Flashcards,
            AIContentTargetType::InteractiveActivity => LearningAssetType::InteractiveActivity,
            default => throw new DomainException('Course asset not found.', 'NOT_FOUND', 404),
        };
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compareAssets(array $left, array $right): int
    {
        $leftOrder = $left['order_index'] ?? PHP_INT_MAX;
        $rightOrder = $right['order_index'] ?? PHP_INT_MAX;

        if ($leftOrder !== $rightOrder) {
            return $leftOrder <=> $rightOrder;
        }

        $leftPublishedAt = (string) ($left['published_at'] ?? '');
        $rightPublishedAt = (string) ($right['published_at'] ?? '');

        if ($leftPublishedAt !== $rightPublishedAt) {
            return $rightPublishedAt <=> $leftPublishedAt;
        }

        return (int) $left['id'] <=> (int) $right['id'];
    }
}
