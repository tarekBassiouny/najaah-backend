<?php

declare(strict_types=1);

namespace App\Services\Surveys;

use App\Enums\CenterType;
use App\Enums\SurveyAssignableType;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScopeType;
use App\Models\Center;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Surveys\Contracts\SurveyResponseServiceInterface;
use App\Support\AuditActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SurveyResponseService implements SurveyResponseServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @return Collection<int, Survey>
     */
    public function getAssignedSurveysForStudent(User $student): Collection
    {
        $centerId = $student->center_id;
        $center = $student->center;

        $query = Survey::query()
            ->with(['questions.options', 'assignments'])
            ->available();

        if (! is_numeric($centerId)) {
            $query->where('scope_type', SurveyScopeType::System);
        } elseif (! $center instanceof Center) {
            return collect();
        } elseif ($center->type === CenterType::Unbranded) {
            $query->where(function ($q) use ($centerId): void {
                $q->where('scope_type', SurveyScopeType::System)
                    ->orWhere(function ($sq) use ($centerId): void {
                        $sq->where('scope_type', SurveyScopeType::Center)
                            ->where('center_id', $centerId);
                    });
            });
        } else {
            $query->where('scope_type', SurveyScopeType::Center)
                ->where('center_id', $centerId);
        }

        $surveys = $query->get();

        return $surveys->filter(fn (Survey $survey): bool => $this->isSurveyAssignedToStudent($survey, $student))
            ->filter(fn (Survey $survey): bool => ! $this->hasUserSubmitted($survey, $student))
            ->sort(fn (Survey $left, Survey $right): int => $this->compareSurveyPriority($left, $right))
            ->take(1)
            ->values();
    }

    /**
     * @param  array<array{question_id: int, answer: mixed}>  $answers
     */
    public function submitResponse(Survey $survey, User $student, array $answers): SurveyResponse
    {
        $this->validateAnswers($survey, $answers);

        if ($this->hasUserSubmitted($survey, $student)) {
            throw new \InvalidArgumentException('You have already submitted a response to this survey');
        }

        return DB::transaction(function () use ($survey, $student, $answers): SurveyResponse {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'user_id' => $student->id,
                'center_id' => $student->center_id,
                'submitted_at' => now(),
            ]);

            foreach ($answers as $answerData) {
                $question = $survey->questions->firstWhere('id', $answerData['question_id']);

                if (! $question instanceof SurveyQuestion) {
                    continue;
                }

                $answerRecord = [
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $question->id,
                ];

                $answer = $answerData['answer'];

                switch ($question->type) {
                    case SurveyQuestionType::SingleChoice:
                    case SurveyQuestionType::Rating:
                        $answerRecord['answer_number'] = is_numeric($answer) ? (int) $answer : null;
                        break;

                    case SurveyQuestionType::YesNo:
                        if (is_bool($answer)) {
                            $answerRecord['answer_number'] = $answer ? 1 : 0;
                        } else {
                            $answerRecord['answer_number'] = is_numeric($answer) ? (int) $answer : null;
                        }

                        break;

                    case SurveyQuestionType::MultipleChoice:
                        $answerRecord['answer_json'] = is_array($answer) ? $answer : [$answer];
                        break;

                    case SurveyQuestionType::Text:
                        $answerRecord['answer_text'] = is_string($answer) ? $answer : null;
                        break;
                }

                SurveyAnswer::create($answerRecord);
            }

            $this->auditLogService->log($student, $response, AuditActions::SURVEY_SUBMITTED, [
                'survey_id' => $survey->id,
            ]);

            return $response->fresh(['answers']) ?? $response;
        });
    }

    public function hasUserSubmitted(Survey $survey, User $student): bool
    {
        $query = SurveyResponse::where('survey_id', $survey->id)
            ->where('user_id', $student->id);

        if ($survey->scope_type === SurveyScopeType::Center) {
            $query->where('center_id', $student->center_id);
        }

        return $query->exists();
    }

    public function isSurveyAssignedToStudent(Survey $survey, User $student): bool
    {
        $center = $student->center;

        if ($survey->scope_type === SurveyScopeType::System) {
            if (is_numeric($student->center_id) && (! $center instanceof Center || $center->type !== CenterType::Unbranded)) {
                return false;
            }
        } elseif (! is_numeric($student->center_id) || $survey->center_id !== (int) $student->center_id) {
            return false;
        }

        $survey->loadMissing('assignments');

        if ($survey->assignments->isEmpty()) {
            return false;
        }

        foreach ($survey->assignments as $assignment) {
            if ($this->isStudentAssigned($student, $assignment->assignable_type, $assignment->assignable_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array{question_id: int, answer: mixed}>  $answers
     */
    public function validateAnswers(Survey $survey, array $answers): void
    {
        $survey->load('questions.options');

        $answeredQuestionIds = collect($answers)->pluck('question_id')->toArray();

        foreach ($survey->questions as $question) {
            if ($question->is_required && ! in_array($question->id, $answeredQuestionIds, true)) {
                $questionName = $question->question_translations['en'] ?? (string) $question->id;
                throw new \InvalidArgumentException(sprintf("Question '%s' is required", $questionName));
            }
        }

        foreach ($answers as $answerData) {
            $question = $survey->questions->firstWhere('id', $answerData['question_id']);

            if (! $question instanceof SurveyQuestion) {
                throw new \InvalidArgumentException('Invalid question ID: '.$answerData['question_id']);
            }

            $this->validateAnswerForQuestionType($question, $answerData['answer']);
        }
    }

    private function isStudentAssigned(User $student, SurveyAssignableType $type, int $id): bool
    {
        return match ($type) {
            SurveyAssignableType::Center => $student->center_id === $id,
            SurveyAssignableType::Course => $student->enrollments()
                ->where('course_id', $id)
                ->exists(),
            SurveyAssignableType::Video => $this->hasStudentFullyPlayedVideo($student, $id),
            SurveyAssignableType::User => $student->id === $id,
            SurveyAssignableType::All => $student->id === $id,
        };
    }

    private function hasStudentFullyPlayedVideo(User $student, int $videoId): bool
    {
        if (! is_numeric($student->center_id)) {
            return false;
        }

        return $student->playbackSessions()
            ->where('video_id', $videoId)
            ->where('is_full_play', true)
            ->whereHas('course', fn ($q) => $q->where('center_id', (int) $student->center_id))
            ->exists();
    }

    private function compareSurveyPriority(Survey $left, Survey $right): int
    {
        if ($left->is_mandatory !== $right->is_mandatory) {
            return $left->is_mandatory ? -1 : 1;
        }

        $leftEndAt = $left->end_at;
        $rightEndAt = $right->end_at;

        if ($leftEndAt === null && $rightEndAt !== null) {
            return 1;
        }

        if ($leftEndAt !== null && $rightEndAt === null) {
            return -1;
        }

        if ($leftEndAt !== null && $rightEndAt !== null) {
            $deadlineComparison = $leftEndAt->getTimestamp() <=> $rightEndAt->getTimestamp();
            if ($deadlineComparison !== 0) {
                return $deadlineComparison;
            }
        }

        $createdComparison = $right->created_at->getTimestamp() <=> $left->created_at->getTimestamp();
        if ($createdComparison !== 0) {
            return $createdComparison;
        }

        return $right->id <=> $left->id;
    }

    private function validateAnswerForQuestionType(SurveyQuestion $question, mixed $answer): void
    {
        switch ($question->type) {
            case SurveyQuestionType::SingleChoice:
                if (! is_numeric($answer)) {
                    throw new \InvalidArgumentException('Single choice answer must be a number');
                }

                $optionIds = $question->options->pluck('id')->toArray();
                if (! in_array((int) $answer, $optionIds, true)) {
                    throw new \InvalidArgumentException('Invalid option selected');
                }

                break;

            case SurveyQuestionType::MultipleChoice:
                if (! is_array($answer)) {
                    throw new \InvalidArgumentException('Multiple choice answer must be an array');
                }

                $optionIds = $question->options->pluck('id')->toArray();
                foreach ($answer as $optionId) {
                    if (! in_array((int) $optionId, $optionIds, true)) {
                        throw new \InvalidArgumentException('Invalid option selected');
                    }
                }

                break;

            case SurveyQuestionType::Rating:
                if (! is_numeric($answer) || $answer < 1 || $answer > 5) {
                    throw new \InvalidArgumentException('Rating must be between 1 and 5');
                }

                break;

            case SurveyQuestionType::YesNo:
                if (! in_array($answer, [0, 1, '0', '1', true, false], true)) {
                    throw new \InvalidArgumentException('Yes/No answer must be 0 or 1');
                }

                break;

            case SurveyQuestionType::Text:
                if (! is_string($answer) && $answer !== null) {
                    throw new \InvalidArgumentException('Text answer must be a string');
                }

                break;
        }
    }
}
