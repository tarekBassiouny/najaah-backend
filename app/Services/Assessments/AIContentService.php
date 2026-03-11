<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Exceptions\DomainException;
use App\Models\AIContentJob;
use App\Models\Assignment;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Assessments\Contracts\AIContentServiceInterface;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AIContentService implements AIContentServiceInterface
{
    public function __construct(
        private readonly QuizServiceInterface $quizService,
        private readonly AssignmentServiceInterface $assignmentService
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function createJob(Center $center, array $data, User $creator): AIContentJob
    {
        $course = Course::query()
            ->whereKey((int) $data['course_id'])
            ->where('center_id', $center->id)
            ->first();

        if (! $course instanceof Course) {
            throw new DomainException('Course not found in this center.', ErrorCodes::NOT_FOUND, 422);
        }

        $sourceType = AIContentSourceType::from((string) $data['source_type']);
        $targetType = AIContentTargetType::from((string) $data['target_type']);
        $sourceId = (int) $data['source_id'];
        $targetId = array_key_exists('target_id', $data) && is_numeric($data['target_id'])
            ? (int) $data['target_id']
            : null;
        $provider = $this->resolveProvider(
            array_key_exists('ai_provider', $data) && is_string($data['ai_provider']) ? $data['ai_provider'] : null
        );
        $model = $this->resolveModel(
            $provider,
            array_key_exists('ai_model', $data) && is_string($data['ai_model']) ? $data['ai_model'] : null
        );

        $this->validateSourceOwnership($center, $course, $sourceType, $sourceId);
        $this->validateTargetOwnership($center, $course, $targetType, $targetId);

        return AIContentJob::query()->create([
            'center_id' => $center->id,
            'course_id' => $course->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => AIContentJobStatus::Pending,
            'generation_config' => $data['generation_config'] ?? [],
            'ai_provider' => $provider,
            'ai_model' => $model,
            'created_by' => $creator->id,
        ]);
    }

    public function processJob(AIContentJob $job): void
    {
        if (! in_array($job->status, [AIContentJobStatus::Pending, AIContentJobStatus::Failed], true)) {
            throw new DomainException('Only pending or failed jobs can be processed.', ErrorCodes::INVALID_STATE, 422);
        }

        $job->update([
            'status' => AIContentJobStatus::Processing,
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $content = $this->extractSourceContent($job);
            if (trim($content) === '') {
                throw new \RuntimeException('Unable to extract source content for AI generation.');
            }

            $prompt = $this->buildPrompt($job, $content);
            $provider = $this->resolveProvider($job->ai_provider);
            $model = $this->resolveModel($provider, $job->ai_model);
            $payload = $this->callAIProvider($prompt, $provider, $model);

            $job->update([
                'status' => AIContentJobStatus::Completed,
                'generated_payload' => $payload,
                'ai_provider' => $provider,
                'ai_model' => $model,
                'prompt_used' => $prompt,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('AI content generation failed', [
                'job_id' => $job->id,
                'error' => $throwable->getMessage(),
            ]);

            $job->update([
                'status' => AIContentJobStatus::Failed,
                'ai_provider' => $this->resolveProvider($job->ai_provider),
                'ai_model' => $this->resolveModel(
                    $this->resolveProvider($job->ai_provider),
                    $job->ai_model
                ),
                'error_message' => $throwable->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string,mixed>  $reviewedPayload
     */
    public function reviewJob(AIContentJob $job, array $reviewedPayload, User $reviewer): AIContentJob
    {
        if (! in_array($job->status, [AIContentJobStatus::Completed, AIContentJobStatus::Approved], true)) {
            throw new DomainException('Only completed or approved jobs can be reviewed.', ErrorCodes::INVALID_STATE, 422);
        }

        if ($reviewedPayload === []) {
            throw new DomainException('reviewed_payload is required.', ErrorCodes::INVALID_STATE, 422);
        }

        $job->update([
            'reviewed_payload' => $reviewedPayload,
            'reviewed_by' => $reviewer->id,
        ]);

        return $job->fresh() ?? $job;
    }

    public function approveJob(AIContentJob $job, User $reviewer): AIContentJob
    {
        if (! in_array($job->status, [AIContentJobStatus::Completed, AIContentJobStatus::Approved], true)) {
            throw new DomainException('Only completed jobs can be approved.', ErrorCodes::INVALID_STATE, 422);
        }

        if (! is_array($job->reviewed_payload) && ! is_array($job->generated_payload)) {
            throw new DomainException('Job has no generated payload to approve.', ErrorCodes::INVALID_STATE, 422);
        }

        $job->update([
            'status' => AIContentJobStatus::Approved,
            'reviewed_by' => $reviewer->id,
        ]);

        return $job->fresh() ?? $job;
    }

    /**
     * @return array<string,mixed>
     */
    public function publishJob(AIContentJob $job, User $publisher): array
    {
        if ($job->status !== AIContentJobStatus::Approved) {
            throw new DomainException('Only approved jobs can be published.', ErrorCodes::INVALID_STATE, 422);
        }

        $payload = is_array($job->reviewed_payload) ? $job->reviewed_payload : $job->generated_payload;
        if (! is_array($payload)) {
            throw new DomainException('Job payload is missing.', ErrorCodes::INVALID_STATE, 422);
        }

        /** @var array<string,mixed> $result */
        $result = DB::transaction(function () use ($job, $publisher, $payload): array {
            $publication = match ($job->target_type) {
                AIContentTargetType::Quiz => $this->publishQuiz($job, $publisher, $payload),
                AIContentTargetType::Assignment => $this->publishAssignment($job, $publisher, $payload),
                AIContentTargetType::Summary,
                AIContentTargetType::Flashcards,
                AIContentTargetType::InteractiveActivity => $this->publishLearningAsset($job, $publisher, $payload),
            };

            $job->update([
                'status' => AIContentJobStatus::Published,
                'published_by' => $publisher->id,
                'published_at' => now(),
            ]);

            return $publication;
        });

        return $result;
    }

    public function discardJob(AIContentJob $job): void
    {
        if ($job->status === AIContentJobStatus::Published) {
            throw new DomainException('Published jobs cannot be discarded.', ErrorCodes::INVALID_STATE, 422);
        }

        $job->update([
            'status' => AIContentJobStatus::Discarded,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getJobStatus(AIContentJob $job): array
    {
        return [
            'id' => $job->id,
            'center_id' => $job->center_id,
            'course_id' => $job->course_id,
            'source_type' => $job->source_type->value,
            'source_id' => $job->source_id,
            'target_type' => $job->target_type->value,
            'target_id' => $job->target_id,
            'status' => $job->status->value,
            'status_label' => $job->status->label(),
            'generation_config' => $job->generation_config,
            'generated_payload' => $job->generated_payload,
            'reviewed_payload' => $job->reviewed_payload,
            'ai_provider' => $job->ai_provider,
            'ai_model' => $job->ai_model,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'published_at' => $job->published_at?->toIso8601String(),
            'created_at' => $job->created_at->toIso8601String(),
            'updated_at' => $job->updated_at->toIso8601String(),
        ];
    }

    private function validateSourceOwnership(
        Center $center,
        Course $course,
        AIContentSourceType $sourceType,
        int $sourceId
    ): void {
        $exists = match ($sourceType) {
            AIContentSourceType::Video => Video::query()
                ->whereKey($sourceId)
                ->where('center_id', $center->id)
                ->whereHas('courses', function ($query) use ($course): void {
                    $query->where('courses.id', $course->id);
                })
                ->exists(),
            AIContentSourceType::Pdf => Pdf::query()
                ->whereKey($sourceId)
                ->where('center_id', $center->id)
                ->whereHas('courses', function ($query) use ($course): void {
                    $query->where('courses.id', $course->id);
                })
                ->exists(),
            AIContentSourceType::Section => Section::query()
                ->whereKey($sourceId)
                ->where('course_id', $course->id)
                ->exists(),
            AIContentSourceType::Course => $sourceId === $course->id,
        };

        if (! $exists) {
            throw new DomainException(
                'Source does not belong to the specified center/course context.',
                ErrorCodes::INVALID_STATE,
                422
            );
        }
    }

    private function validateTargetOwnership(
        Center $center,
        Course $course,
        AIContentTargetType $targetType,
        ?int $targetId
    ): void {
        if ($targetId === null) {
            return;
        }

        $exists = match ($targetType) {
            AIContentTargetType::Quiz => Quiz::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->exists(),
            AIContentTargetType::Assignment => Assignment::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->exists(),
            AIContentTargetType::Summary => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::Summary)
                ->exists(),
            AIContentTargetType::Flashcards => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::Flashcards)
                ->exists(),
            AIContentTargetType::InteractiveActivity => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::InteractiveActivity)
                ->exists(),
        };

        if (! $exists) {
            throw new DomainException('Target does not belong to the specified center/course context.', ErrorCodes::NOT_FOUND, 422);
        }
    }

    private function extractSourceContent(AIContentJob $job): string
    {
        return match ($job->source_type) {
            AIContentSourceType::Video => $this->extractVideoContent($job),
            AIContentSourceType::Pdf => $this->extractPdfContent($job),
            AIContentSourceType::Section => $this->extractSectionContent($job),
            AIContentSourceType::Course => $this->extractCourseContent($job),
        };
    }

    private function extractVideoContent(AIContentJob $job): string
    {
        $video = Video::query()
            ->whereKey($job->source_id)
            ->where('center_id', $job->center_id)
            ->firstOrFail();

        /** @var mixed $transcript */
        $transcript = $video->getAttribute('transcript');

        return trim(implode("\n\n", array_filter([
            'Video title: '.$video->translate('title'),
            'Video description: '.($video->translate('description') ?? ''),
            is_string($transcript) ? $transcript : null,
        ])));
    }

    private function extractPdfContent(AIContentJob $job): string
    {
        $pdf = Pdf::query()
            ->whereKey($job->source_id)
            ->where('center_id', $job->center_id)
            ->firstOrFail();

        /** @var mixed $textContent */
        $textContent = $pdf->getAttribute('text_content');

        return trim(implode("\n\n", array_filter([
            'PDF title: '.$pdf->translate('title'),
            'PDF description: '.($pdf->translate('description') ?? ''),
            is_string($textContent) ? $textContent : null,
        ])));
    }

    private function extractSectionContent(AIContentJob $job): string
    {
        $section = Section::query()
            ->whereKey($job->source_id)
            ->where('course_id', $job->course_id)
            ->with(['videos', 'pdfs'])
            ->firstOrFail();

        $parts = [
            'Section title: '.$section->translate('title'),
            'Section description: '.($section->translate('description') ?? ''),
        ];

        foreach ($section->videos as $video) {
            /** @var mixed $transcript */
            $transcript = $video->getAttribute('transcript');
            $parts[] = 'Video: '.$video->translate('title');
            if (is_string($transcript) && $transcript !== '') {
                $parts[] = $transcript;
            }
        }

        foreach ($section->pdfs as $pdf) {
            /** @var mixed $textContent */
            $textContent = $pdf->getAttribute('text_content');
            $parts[] = 'PDF: '.$pdf->translate('title');
            if (is_string($textContent) && $textContent !== '') {
                $parts[] = $textContent;
            }
        }

        return trim(implode("\n\n", $parts));
    }

    private function extractCourseContent(AIContentJob $job): string
    {
        $course = Course::query()
            ->whereKey($job->source_id)
            ->where('center_id', $job->center_id)
            ->with(['sections.videos', 'sections.pdfs'])
            ->firstOrFail();

        $parts = [
            'Course title: '.$course->translate('title'),
            'Course description: '.($course->translate('description') ?? ''),
        ];

        foreach ($course->sections as $section) {
            $parts[] = 'Section: '.$section->translate('title');

            foreach ($section->videos as $video) {
                $parts[] = 'Video: '.$video->translate('title');
            }

            foreach ($section->pdfs as $pdf) {
                $parts[] = 'PDF: '.$pdf->translate('title');
            }
        }

        return trim(implode("\n\n", $parts));
    }

    private function buildPrompt(AIContentJob $job, string $content): string
    {
        $jsonShape = match ($job->target_type) {
            AIContentTargetType::Quiz => <<<'JSON'
{
  "quiz": {
    "title": "string",
    "description": "string"
  },
  "questions": [
    {
      "question": "string",
      "options": [
        {"text": "string", "is_correct": true},
        {"text": "string", "is_correct": false},
        {"text": "string", "is_correct": false},
        {"text": "string", "is_correct": false}
      ],
      "explanation": "string",
      "points": 1
    }
  ]
}
JSON,
            AIContentTargetType::Assignment => <<<'JSON'
{
  "assignment": {
    "title": "string",
    "description": "string",
    "submission_types": [0,1,2],
    "max_points": 100,
    "passing_score": 60
  }
}
JSON,
            AIContentTargetType::Summary => <<<'JSON'
{
  "title": "string",
  "content": "string"
}
JSON,
            AIContentTargetType::Flashcards => <<<'JSON'
{
  "title": "string",
  "cards": [
    {"front": "string", "back": "string"}
  ]
}
JSON,
            AIContentTargetType::InteractiveActivity => <<<'JSON'
{
  "title": "string",
  "instructions": "string",
  "steps": [
    {"title": "string", "description": "string", "estimated_seconds": 60}
  ]
}
JSON,
        };

        return <<<PROMPT
You are an educational content specialist.
Generate structured {$job->target_type->value} content from the source content below.

SOURCE CONTENT:
{$content}

RULES:
- Output valid JSON only.
- Keep it concise and pedagogically useful.
- Use clear language suitable for high-school and college learners.

OUTPUT JSON SHAPE:
{$jsonShape}
PROMPT;
    }

    /**
     * @return array<string,mixed>
     */
    private function callAIProvider(string $prompt, string $provider, string $model): array
    {
        /** @var array<string,mixed> $payload */
        $payload = match ($provider) {
            'anthropic' => $this->callAnthropic($prompt, $model),
            'gemini' => $this->callGemini($prompt, $model),
            default => $this->callOpenAI($prompt, $model),
        };

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    private function callOpenAI(string $prompt, string $model): array
    {
        $apiKey = (string) config('services.openai.api_key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('OpenAI response content is empty.');
        }

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<string,mixed>
     */
    private function callAnthropic(string $prompt, string $model): array
    {
        $apiKey = (string) config('services.anthropic.api_key');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic API request failed: '.$response->body());
        }

        $content = $response->json('content.0.text');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Anthropic response content is empty.');
        }

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<string,mixed>
     */
    private function callGemini(string $prompt, string $model): array
    {
        $apiKey = trim((string) config('services.gemini.api_key'));
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is missing.');
        }

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($model)
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($endpoint.'?key='.$apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API request failed: '.$response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Gemini response content is empty.');
        }

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<string,mixed>
     */
    private function parseAIResponse(string $content): array
    {
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = (string) preg_replace('/^```(?:json)?\n?/', '', $trimmed);
            $trimmed = (string) preg_replace('/\n?```$/', '', $trimmed);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Failed to parse AI response as JSON object.');
        }

        return $decoded;
    }

    private function resolveProvider(?string $provider): string
    {
        $configured = (string) config('services.ai.provider', 'openai');
        $normalized = strtolower(trim((string) ($provider ?? $configured)));

        return in_array($normalized, ['openai', 'anthropic', 'gemini'], true) ? $normalized : 'openai';
    }

    private function resolveModel(string $provider, ?string $model): string
    {
        $trimmedModel = trim((string) $model);
        if ($trimmedModel !== '') {
            return $trimmedModel;
        }

        $configuredModel = trim((string) config('services.ai.model', ''));
        if ($configuredModel !== '') {
            return $configuredModel;
        }

        return match ($provider) {
            'anthropic' => 'claude-3-5-sonnet-20241022',
            'gemini' => 'gemini-1.5-flash',
            default => 'gpt-4o-mini',
        };
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function publishQuiz(AIContentJob $job, User $publisher, array $payload): array
    {
        $course = Course::query()->findOrFail($job->course_id);

        /** @var Quiz $quiz */
        $quiz = $job->target_id !== null
            ? Quiz::query()->whereKey($job->target_id)->where('center_id', $job->center_id)->firstOrFail()
            : $this->quizService->create($course, [
                'title_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.title'), 'AI Generated Quiz'),
                'description_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.description')),
                'attachable_type' => $job->source_type->value,
                'attachable_id' => $job->source_id,
                'is_active' => true,
            ], $publisher);

        if ($job->target_id !== null) {
            $this->quizService->update($quiz, [
                'title_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.title'), (string) $quiz->translate('title')),
                'description_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.description')),
                'attachable_type' => $job->source_type->value,
                'attachable_id' => $job->source_id,
            ]);
        }

        $questions = data_get($payload, 'questions');
        if (! is_array($questions)) {
            throw new DomainException('Quiz payload must include questions array.', ErrorCodes::INVALID_STATE, 422);
        }

        $maxOrder = (int) ($quiz->questions()->max('order_index') ?? -1);
        $addedCount = 0;

        foreach ($questions as $questionData) {
            if (! is_array($questionData)) {
                continue;
            }

            $questionText = data_get($questionData, 'question');
            $options = data_get($questionData, 'options');

            if (! is_string($questionText) || ! is_array($options) || $options === []) {
                continue;
            }

            $maxOrder++;
            $question = QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'question_translations' => $this->normalizeTranslations($questionText, 'Question'),
                'question_type' => 0,
                'explanation_translations' => $this->normalizeTranslations(data_get($questionData, 'explanation')),
                'points' => is_numeric(data_get($questionData, 'points')) ? (float) data_get($questionData, 'points') : 1.00,
                'order_index' => $maxOrder,
                'is_active' => true,
                'ai_generated' => true,
                'ai_source_type' => $job->source_type->value,
                'ai_source_id' => $job->source_id,
            ]);

            foreach (array_values($options) as $index => $option) {
                if (is_string($option)) {
                    $optionText = $option;
                    $isCorrect = false;
                } elseif (is_array($option)) {
                    $optionText = (string) data_get($option, 'text', '');
                    $isCorrect = (bool) data_get($option, 'is_correct', false);
                } else {
                    continue;
                }

                if ($optionText === '') {
                    continue;
                }

                QuizAnswer::query()->create([
                    'quiz_question_id' => $question->id,
                    'answer_translations' => $this->normalizeTranslations($optionText, 'Option'),
                    'is_correct' => $isCorrect,
                    'order_index' => $index,
                ]);
            }

            $addedCount++;
        }

        return [
            'target_type' => AIContentTargetType::Quiz->value,
            'target_id' => $quiz->id,
            'questions_added' => $addedCount,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function publishAssignment(AIContentJob $job, User $publisher, array $payload): array
    {
        $course = Course::query()->findOrFail($job->course_id);
        $assignmentPayload = data_get($payload, 'assignment');
        if (! is_array($assignmentPayload)) {
            $assignmentPayload = $payload;
        }

        $data = [
            'title_translations' => $this->normalizeTranslations(data_get($assignmentPayload, 'title'), 'AI Generated Assignment'),
            'description_translations' => $this->normalizeTranslations(data_get($assignmentPayload, 'description')),
            'attachable_type' => $job->source_type->value,
            'attachable_id' => $job->source_id,
            'submission_types' => $this->normalizeSubmissionTypes(data_get($assignmentPayload, 'submission_types')),
            'max_points' => is_numeric(data_get($assignmentPayload, 'max_points'))
                ? (float) data_get($assignmentPayload, 'max_points')
                : 100.0,
            'passing_score' => is_numeric(data_get($assignmentPayload, 'passing_score'))
                ? (float) data_get($assignmentPayload, 'passing_score')
                : 60.0,
            'is_active' => true,
        ];

        /** @var Assignment $assignment */
        $assignment = $job->target_id !== null
            ? Assignment::query()->whereKey($job->target_id)->where('center_id', $job->center_id)->firstOrFail()
            : $this->assignmentService->create($course, $data, $publisher);

        if ($job->target_id !== null) {
            $assignment = $this->assignmentService->update($assignment, $data);
        }

        return [
            'target_type' => AIContentTargetType::Assignment->value,
            'target_id' => $assignment->id,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function publishLearningAsset(AIContentJob $job, User $publisher, array $payload): array
    {
        $assetType = match ($job->target_type) {
            AIContentTargetType::Summary => LearningAssetType::Summary,
            AIContentTargetType::Flashcards => LearningAssetType::Flashcards,
            AIContentTargetType::InteractiveActivity => LearningAssetType::InteractiveActivity,
            default => throw new DomainException('Invalid learning asset target type.', ErrorCodes::INVALID_STATE, 422),
        };

        /** @var LearningAsset|null $asset */
        $asset = $job->target_id !== null
            ? LearningAsset::query()
                ->whereKey($job->target_id)
                ->where('center_id', $job->center_id)
                ->where('course_id', $job->course_id)
                ->where('asset_type', $assetType)
                ->first()
            : null;

        $attributes = [
            'center_id' => $job->center_id,
            'course_id' => $job->course_id,
            'attachable_type' => $job->source_type->value,
            'attachable_id' => $job->source_id,
            'asset_type' => $assetType,
            'status' => LearningAssetStatus::Published,
            'title_translations' => $this->normalizeTranslations(data_get($payload, 'title'), ucfirst(str_replace('_', ' ', $assetType->value))),
            'content_translations' => $this->normalizeTranslations(data_get($payload, 'content')),
            'payload' => $payload,
            'is_active' => true,
            'published_by' => $publisher->id,
            'published_at' => now(),
            'updated_by' => $publisher->id,
        ];

        if (! $asset instanceof LearningAsset) {
            $attributes['created_by'] = $publisher->id;
            $asset = LearningAsset::query()->create($attributes);
        } else {
            $asset->update($attributes);
        }

        return [
            'target_type' => $job->target_type->value,
            'target_id' => $asset->id,
        ];
    }

    /**
     * @return array<string,string>|null
     */
    private function normalizeTranslations(mixed $value, ?string $fallback = null): ?array
    {
        if (is_array($value)) {
            $translations = [];
            foreach (['en', 'ar'] as $locale) {
                $raw = data_get($value, $locale);
                if (is_string($raw)) {
                    $translations[$locale] = $raw;
                }
            }

            if ($translations !== []) {
                return $translations;
            }
        }

        if (is_string($value) && $value !== '') {
            return ['en' => $value];
        }

        if (is_string($fallback) && $fallback !== '') {
            return ['en' => $fallback];
        }

        return null;
    }

    /**
     * @return array<int>
     */
    private function normalizeSubmissionTypes(mixed $value): array
    {
        if (! is_array($value)) {
            return [0];
        }

        $normalized = collect($value)
            ->filter(static fn ($item): bool => is_numeric($item))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => in_array($item, [0, 1, 2], true))
            ->values()
            ->all();

        return $normalized === [] ? [0] : $normalized;
    }
}
