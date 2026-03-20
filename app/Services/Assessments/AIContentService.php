<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Enums\QuestionType;
use App\Enums\TextExtractionStatus;
use App\Exceptions\DomainException;
use App\Exceptions\RateLimitException;
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
use App\Services\AI\Contracts\AIIntegrationServiceInterface;
use App\Services\Assessments\Contracts\AIContentServiceInterface;
use App\Services\Assessments\Contracts\AssignmentServiceInterface;
use App\Services\Assessments\Contracts\QuizServiceInterface;
use App\Services\Assessments\Validators\AIOutputValidatorInterface;
use App\Services\Assessments\Validators\AssignmentOutputValidator;
use App\Services\Assessments\Validators\FlashcardsOutputValidator;
use App\Services\Assessments\Validators\InteractiveActivityOutputValidator;
use App\Services\Assessments\Validators\QuizOutputValidator;
use App\Services\Assessments\Validators\SummaryOutputValidator;
use App\Support\ErrorCodes;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AIContentService implements AIContentServiceInterface
{
    public function __construct(
        private readonly QuizServiceInterface $quizService,
        private readonly AssignmentServiceInterface $assignmentService,
        private readonly AIIntegrationServiceInterface $aiIntegrationService,
        private readonly PromptBuilder $promptBuilder
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
        $this->validateSourceOwnership($center, $course, $sourceType, $sourceId);
        $this->validateTargetOwnership($center, $course, $sourceType, $sourceId, $targetType, $targetId);

        $resolvedConfig = $this->aiIntegrationService->resolveProviderAndModel(
            $center,
            array_key_exists('ai_provider', $data) && is_string($data['ai_provider']) ? $data['ai_provider'] : null,
            array_key_exists('ai_model', $data) && is_string($data['ai_model']) ? $data['ai_model'] : null
        );

        $provider = (string) $resolvedConfig['provider'];
        $model = (string) $resolvedConfig['model'];

        $sourceContent = $this->extractSourceContentByContext($center->id, $course->id, $sourceType, $sourceId);
        $inputChars = mb_strlen($sourceContent);
        $estimatedInputTokens = $this->aiIntegrationService->estimateTokensFromChars($inputChars);
        $estimatedOutputTokens = $this->aiIntegrationService->defaultEstimatedOutputTokens($center, $provider);

        $this->aiIntegrationService->assertCanCreateJob(
            $center,
            $provider,
            $inputChars,
            $estimatedInputTokens,
            $estimatedOutputTokens
        );

        return AIContentJob::query()->create([
            'center_id' => $center->id,
            'course_id' => $course->id,
            'batch_key' => array_key_exists('batch_key', $data) && is_string($data['batch_key']) ? $data['batch_key'] : null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'language' => is_string($data['language'] ?? null) ? $data['language'] : 'ar',
            'status' => AIContentJobStatus::Pending,
            'generation_config' => $data['generation_config'] ?? [],
            'ai_provider' => $provider,
            'ai_model' => $model,
            'estimated_input_tokens' => $estimatedInputTokens,
            'estimated_output_tokens' => $estimatedOutputTokens,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array{batch_key:string,jobs:list<AIContentJob>}
     */
    public function createBatch(Center $center, array $data, User $creator): array
    {
        return DB::transaction(function () use ($center, $data, $creator): array {
            $batchKey = (string) Str::uuid();
            /** @var list<AIContentJob> $jobs */
            $jobs = [];

            /** @var array<int,array<string,mixed>> $assets */
            $assets = $data['assets'];

            foreach ($assets as $asset) {
                $jobs[] = $this->createJob($center, [
                    'course_id' => $data['course_id'],
                    'batch_key' => $batchKey,
                    'source_type' => $data['source_type'],
                    'source_id' => $data['source_id'],
                    'target_type' => $asset['target_type'],
                    'target_id' => $asset['target_id'] ?? null,
                    'language' => $data['language'] ?? 'ar',
                    'ai_provider' => $asset['ai_provider'] ?? null,
                    'ai_model' => $asset['ai_model'] ?? null,
                    'generation_config' => $asset['generation_config'] ?? [],
                ], $creator);
            }

            return [
                'batch_key' => $batchKey,
                'jobs' => $jobs,
            ];
        });
    }

    public function processJob(AIContentJob $job): void
    {
        if (! in_array($job->status, [AIContentJobStatus::Pending, AIContentJobStatus::Failed], true)) {
            throw new DomainException('Only pending or failed jobs can be processed.', ErrorCodes::INVALID_STATE, 422);
        }

        $center = Center::query()->find($job->center_id);
        if (! $center instanceof Center) {
            throw new DomainException('Center not found for AI content job.', ErrorCodes::NOT_FOUND, 404);
        }

        $job->update([
            'status' => AIContentJobStatus::Processing,
            'started_at' => now(),
            'error_message' => null,
            'validation_warnings' => null,
        ]);

        $validationWarnings = [];

        try {
            $content = $this->extractSourceContent($job);
            if (trim($content) === '') {
                throw new \RuntimeException('Unable to extract source content for AI generation.');
            }

            $prompts = $this->promptBuilder->build($job, $content);
            $provider = is_string($job->ai_provider) ? $job->ai_provider : '';
            $model = is_string($job->ai_model) ? $job->ai_model : '';
            if ($provider === '' || $model === '') {
                throw new \RuntimeException('AI provider/model is missing on job.');
            }

            $inputChars = mb_strlen($content);
            $estimatedInputTokens = $this->aiIntegrationService->estimateTokensFromChars($inputChars);
            $apiKey = $this->aiIntegrationService->resolveApiKey($provider);
            if ($apiKey === '') {
                throw new \RuntimeException(sprintf('API key is not configured for provider [%s].', $provider));
            }

            $payload = $this->callAIProvider($prompts['system'], $prompts['user'], $provider, $model, $apiKey);
            $promptHistory = $prompts;
            $validationWarnings = $this->validateOutputPayload($job, $payload);

            if ($validationWarnings !== []) {
                $retryPrompts = $this->promptBuilder->buildRetryPrompt($job, $content, $validationWarnings);
                $payload = $this->callAIProvider($retryPrompts['system'], $retryPrompts['user'], $provider, $model, $apiKey);

                $retryValidationErrors = $this->validateOutputPayload($job, $payload);
                if ($retryValidationErrors !== []) {
                    $validationWarnings = array_values(array_unique([...$validationWarnings, ...$retryValidationErrors]));

                    throw new \RuntimeException('AI output validation failed after retry.');
                }

                $promptHistory = [
                    'initial' => $prompts,
                    'retry' => $retryPrompts,
                ];
            }

            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $outputChars = is_string($encodedPayload) ? mb_strlen($encodedPayload) : 0;
            $actualOutputTokens = $this->aiIntegrationService->estimateTokensFromChars($outputChars);

            $this->aiIntegrationService->assertCanStoreOutput(
                $center,
                $provider,
                $job,
                $outputChars,
                $actualOutputTokens
            );

            $job->update([
                'status' => AIContentJobStatus::Completed,
                'generated_payload' => $payload,
                'ai_provider' => $provider,
                'ai_model' => $model,
                'estimated_input_tokens' => $estimatedInputTokens,
                'estimated_output_tokens' => $actualOutputTokens,
                'prompt_used' => $this->encodePromptHistory($promptHistory),
                'validation_warnings' => $validationWarnings === [] ? null : $validationWarnings,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('AI content generation failed', [
                'job_id' => $job->id,
                'error' => $throwable->getMessage(),
            ]);

            if ($this->shouldRetryProviderFailure($throwable)) {
                $job->update([
                    'status' => AIContentJobStatus::Pending,
                    'error_message' => $throwable->getMessage(),
                    'validation_warnings' => $validationWarnings === [] ? null : $validationWarnings,
                    'completed_at' => null,
                ]);

                throw $throwable;
            }

            $job->update([
                'status' => AIContentJobStatus::Failed,
                'error_message' => $throwable->getMessage(),
                'validation_warnings' => $validationWarnings === [] ? null : $validationWarnings,
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

        $validationErrors = $this->validateOutputPayload($job, $reviewedPayload);
        if ($validationErrors !== []) {
            throw new DomainException(
                'Reviewed payload is invalid: '.implode(' ', $validationErrors),
                ErrorCodes::INVALID_STATE,
                422
            );
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
            'batch_key' => $job->batch_key,
            'source_type' => $job->source_type->value,
            'source_id' => $job->source_id,
            'target_type' => $job->target_type->value,
            'target_id' => $job->target_id,
            'status' => $job->status->value,
            'status_label' => $job->status->label(),
            'generation_config' => $job->generation_config,
            'generated_payload' => $job->generated_payload,
            'reviewed_payload' => $job->reviewed_payload,
            'validation_warnings' => $job->validation_warnings,
            'ai_provider' => $job->ai_provider,
            'ai_model' => $job->ai_model,
            'estimated_input_tokens' => $job->estimated_input_tokens,
            'estimated_output_tokens' => $job->estimated_output_tokens,
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
        AIContentSourceType $sourceType,
        int $sourceId,
        AIContentTargetType $targetType,
        ?int $targetId
    ): void {
        if ($targetId === null) {
            return;
        }

        $target = match ($targetType) {
            AIContentTargetType::Quiz => Quiz::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->first(),
            AIContentTargetType::Assignment => Assignment::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->first(),
            AIContentTargetType::Summary => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::Summary)
                ->first(),
            AIContentTargetType::Flashcards => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::Flashcards)
                ->first(),
            AIContentTargetType::InteractiveActivity => LearningAsset::query()
                ->whereKey($targetId)
                ->where('center_id', $center->id)
                ->where('course_id', $course->id)
                ->where('asset_type', LearningAssetType::InteractiveActivity)
                ->first(),
        };

        if ($target === null) {
            throw new DomainException('Target does not belong to the specified center/course context.', ErrorCodes::NOT_FOUND, 422);
        }

        $attachableType = $target->getAttribute('attachable_type');
        $attachableId = $target->getAttribute('attachable_id');

        if ($attachableType !== null && $attachableId !== null) {
            $matchesSource = (string) $attachableType === $sourceType->value
                && (int) $attachableId === $sourceId;

            if (! $matchesSource) {
                throw new DomainException(
                    'Target does not belong to the specified source item.',
                    ErrorCodes::INVALID_STATE,
                    422
                );
            }
        }
    }

    private function extractSourceContent(AIContentJob $job): string
    {
        return $this->extractSourceContentByContext($job->center_id, $job->course_id, $job->source_type, $job->source_id);
    }

    private function extractSourceContentByContext(
        int $centerId,
        int $courseId,
        AIContentSourceType $sourceType,
        int $sourceId
    ): string {
        return match ($sourceType) {
            AIContentSourceType::Video => $this->extractVideoContent($centerId, $sourceId),
            AIContentSourceType::Pdf => $this->extractPdfContent($centerId, $sourceId),
            AIContentSourceType::Section => $this->extractSectionContent($courseId, $sourceId),
            AIContentSourceType::Course => $this->extractCourseContent($centerId, $sourceId),
        };
    }

    private function extractVideoContent(int $centerId, int $sourceId): string
    {
        $video = Video::query()
            ->whereKey($sourceId)
            ->where('center_id', $centerId)
            ->firstOrFail();
        $this->assertVideoSourceReady($video);

        /** @var mixed $transcript */
        $transcript = $video->getAttribute('transcript');

        return trim(implode("\n\n", array_filter([
            'Video title: '.$video->translate('title'),
            'Video description: '.($video->translate('description') ?? ''),
            is_string($transcript) ? $transcript : null,
        ])));
    }

    private function extractPdfContent(int $centerId, int $sourceId): string
    {
        $pdf = Pdf::query()
            ->whereKey($sourceId)
            ->where('center_id', $centerId)
            ->firstOrFail();
        $this->assertPdfSourceReady($pdf);

        /** @var mixed $textContent */
        $textContent = $pdf->getAttribute('text_content');

        return trim(implode("\n\n", array_filter([
            'PDF title: '.$pdf->translate('title'),
            'PDF description: '.($pdf->translate('description') ?? ''),
            is_string($textContent) ? $textContent : null,
        ])));
    }

    private function assertVideoSourceReady(Video $video): void
    {
        /** @var mixed $transcript */
        $transcript = $video->getAttribute('transcript');

        if (! is_string($transcript) || trim($transcript) === '') {
            throw new DomainException(
                'Video transcript is required before AI generation.',
                ErrorCodes::TRANSCRIPT_NOT_FOUND,
                422
            );
        }
    }

    private function assertPdfSourceReady(Pdf $pdf): void
    {
        $status = $pdf->text_extraction_status;

        if ($status === null || $status === TextExtractionStatus::Pending || $status === TextExtractionStatus::Processing) {
            throw new DomainException(
                'PDF text extraction is still in progress.',
                ErrorCodes::PDF_NOT_READY,
                422
            );
        }

        if ($status === TextExtractionStatus::Failed || $status === TextExtractionStatus::Skipped) {
            throw new DomainException(
                'PDF text extraction failed or returned no usable text.',
                ErrorCodes::PDF_TEXT_EXTRACTION_FAILED,
                422
            );
        }

        /** @var mixed $textContent */
        $textContent = $pdf->getAttribute('text_content');

        if (! is_string($textContent) || trim($textContent) === '') {
            throw new DomainException(
                'PDF text extraction failed or returned no usable text.',
                ErrorCodes::PDF_TEXT_EXTRACTION_FAILED,
                422
            );
        }
    }

    private function extractSectionContent(int $courseId, int $sourceId): string
    {
        $section = Section::query()
            ->whereKey($sourceId)
            ->where('course_id', $courseId)
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

    private function extractCourseContent(int $centerId, int $sourceId): string
    {
        $course = Course::query()
            ->whereKey($sourceId)
            ->where('center_id', $centerId)
            ->with(['videos', 'pdfs', 'sections.videos', 'sections.pdfs'])
            ->firstOrFail();

        $parts = [
            'Course title: '.$course->translate('title'),
            'Course description: '.($course->translate('description') ?? ''),
        ];

        $directVideos = $course->videos->filter(static fn (Video $video): bool => $video->pivot?->section_id === null);
        foreach ($directVideos as $video) {
            /** @var mixed $transcript */
            $transcript = $video->getAttribute('transcript');
            $parts[] = 'Video: '.$video->translate('title');
            if (is_string($transcript) && $transcript !== '') {
                $parts[] = $transcript;
            }
        }

        $directPdfs = $course->pdfs->filter(static fn (Pdf $pdf): bool => $pdf->pivot?->section_id === null);
        foreach ($directPdfs as $pdf) {
            /** @var mixed $textContent */
            $textContent = $pdf->getAttribute('text_content');
            $parts[] = 'PDF: '.$pdf->translate('title');
            if (is_string($textContent) && $textContent !== '') {
                $parts[] = $textContent;
            }
        }

        foreach ($course->sections as $section) {
            $parts[] = 'Section: '.$section->translate('title');

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
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * @return array<string,mixed>
     */
    private function callAIProvider(
        string $systemPrompt,
        string $userPrompt,
        string $provider,
        string $model,
        string $apiKey
    ): array {
        /** @var array<string,mixed> $payload */
        $payload = match ($provider) {
            'anthropic' => $this->callAnthropic($systemPrompt, $userPrompt, $model, $apiKey),
            'gemini' => $this->callGemini($systemPrompt, $userPrompt, $model, $apiKey),
            default => $this->callOpenAI($systemPrompt, $userPrompt, $model, $apiKey),
        };

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    private function callOpenAI(string $systemPrompt, string $userPrompt, string $model, string $apiKey): array
    {
        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
            'temperature' => 0.4,
        ]);

        $this->assertProviderResponseSuccessful($response, 'OpenAI');

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('OpenAI response content is empty.');
        }

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<string,mixed>
     */
    private function callAnthropic(string $systemPrompt, string $userPrompt, string $model, string $apiKey): array
    {
        $response = Http::timeout(120)->withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $systemPrompt."\nReturn JSON only with no markdown or extra commentary.",
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $this->assertProviderResponseSuccessful($response, 'Anthropic');

        $content = $response->json('content.0.text');
        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('Anthropic response content is empty.');
        }

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<string,mixed>
     */
    private function callGemini(string $systemPrompt, string $userPrompt, string $model, string $apiKey): array
    {
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            rawurlencode($model)
        );

        $response = Http::timeout(120)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post($endpoint.'?key='.$apiKey, [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.4,
            ],
        ]);

        $this->assertProviderResponseSuccessful($response, 'Gemini');

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

    private function assertProviderResponseSuccessful(Response $response, string $providerLabel): void
    {
        if ($response->status() === 429) {
            throw new RateLimitException(
                strtolower($providerLabel),
                sprintf('%s rate limit exceeded.', $providerLabel)
            );
        }

        if (! $response->successful()) {
            throw new \RuntimeException(sprintf(
                '%s API request failed with status %d: %s',
                $providerLabel,
                $response->status(),
                $response->body()
            ));
        }
    }

    private function shouldRetryProviderFailure(\Throwable $throwable): bool
    {
        return $throwable instanceof RateLimitException
            || $throwable instanceof ConnectionException;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function publishQuiz(AIContentJob $job, User $publisher, array $payload): array
    {
        $course = Course::query()->findOrFail($job->course_id);
        $target = $job->target_id !== null
            ? Quiz::query()->whereKey($job->target_id)->where('center_id', $job->center_id)->firstOrFail()
            : null;

        $quiz = $target instanceof Quiz
            ? $this->resolveQuizPublicationTarget($target, $publisher)
            : $this->quizService->create($course, [
                'title_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.title'), 'AI Generated Quiz', $job->language),
                'description_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.description'), null, $job->language),
                'attachable_type' => $job->source_type->value,
                'attachable_id' => $job->source_id,
                'is_active' => false,
            ], $publisher);

        $quiz = $this->quizService->update($quiz, [
            'title_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.title'), $quiz->translate('title'), $job->language),
            'description_translations' => $this->normalizeTranslations(data_get($payload, 'quiz.description'), null, $job->language),
            'attachable_type' => $job->source_type->value,
            'attachable_id' => $job->source_id,
            'is_active' => true,
        ]);

        $questions = data_get($payload, 'questions');
        if (! is_array($questions)) {
            throw new DomainException('Quiz payload must include questions array.', ErrorCodes::INVALID_STATE, 422);
        }

        $addedCount = $this->replaceQuizQuestions($quiz, $questions, $job);
        $this->deactivateConflictingQuizzes($quiz, $job);

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
            'title_translations' => $this->normalizeTranslations(data_get($assignmentPayload, 'title'), 'AI Generated Assignment', $job->language),
            'description_translations' => $this->normalizeTranslations(data_get($assignmentPayload, 'description'), null, $job->language),
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

        $target = $job->target_id !== null
            ? Assignment::query()->whereKey($job->target_id)->where('center_id', $job->center_id)->firstOrFail()
            : null;

        $assignment = $target instanceof Assignment
            ? $this->resolveAssignmentPublicationTarget($target, $publisher)
            : $this->assignmentService->create($course, [...$data, 'is_active' => false], $publisher);

        $assignment = $this->assignmentService->update($assignment, [...$data, 'is_active' => true]);
        $this->deactivateConflictingAssignments($assignment, $job);

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

        $target = $job->target_id !== null
            ? LearningAsset::query()
                ->whereKey($job->target_id)
                ->where('center_id', $job->center_id)
                ->where('course_id', $job->course_id)
                ->where('asset_type', $assetType)
                ->first()
            : null;

        $asset = $target instanceof LearningAsset
            ? $this->resolveLearningAssetPublicationTarget($target, $publisher)
            : null;

        $attributes = [
            'center_id' => $job->center_id,
            'course_id' => $job->course_id,
            'attachable_type' => $job->source_type->value,
            'attachable_id' => $job->source_id,
            'asset_type' => $assetType,
            'status' => LearningAssetStatus::Published,
            'title_translations' => $this->normalizeTranslations(data_get($payload, 'title'), ucfirst(str_replace('_', ' ', $assetType->value)), $job->language),
            'content_translations' => $this->normalizeTranslations(data_get($payload, 'content'), null, $job->language),
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

        LearningAsset::query()
            ->where('center_id', $job->center_id)
            ->where('course_id', $job->course_id)
            ->where('attachable_type', $job->source_type->value)
            ->where('attachable_id', $job->source_id)
            ->where('asset_type', $assetType)
            ->whereKeyNot($asset->id)
            ->where('status', LearningAssetStatus::Published)
            ->update([
                'status' => LearningAssetStatus::Archived,
                'is_active' => false,
                'updated_by' => $publisher->id,
            ]);

        return [
            'target_type' => $job->target_type->value,
            'target_id' => $asset->id,
        ];
    }

    /**
     * @param  array{system:string,user:string}  $prompts
     */
    private function encodePrompts(array $prompts): string
    {
        $encoded = json_encode([
            'system' => $this->promptAuditData($prompts['system']),
            'user' => $this->promptAuditData($prompts['user']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (is_string($encoded)) {
            return $encoded;
        }

        return json_encode([
            'system' => ['chars' => mb_strlen($prompts['system'])],
            'user' => ['chars' => mb_strlen($prompts['user'])],
        ], JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param  array{system:string,user:string}|array{initial:array{system:string,user:string},retry:array{system:string,user:string}}  $promptHistory
     */
    private function encodePromptHistory(array $promptHistory): string
    {
        if (isset($promptHistory['system'], $promptHistory['user'])) {
            /** @var array{system:string,user:string} $promptHistory */
            return $this->encodePrompts($promptHistory);
        }

        $encoded = json_encode([
            'initial' => [
                'system' => $this->promptAuditData($promptHistory['initial']['system']),
                'user' => $this->promptAuditData($promptHistory['initial']['user']),
            ],
            'retry' => [
                'system' => $this->promptAuditData($promptHistory['retry']['system']),
                'user' => $this->promptAuditData($promptHistory['retry']['user']),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '';
    }

    /**
     * @return array{chars:int,sha256:string,preview:string,tail_preview:?string,truncated:bool}
     */
    private function promptAuditData(string $prompt): array
    {
        $length = mb_strlen($prompt);
        $preview = Str::limit($prompt, 4000, '...[truncated]');
        $tailPreview = $length > 1000 ? mb_substr($prompt, -1000) : null;

        return [
            'chars' => $length,
            'sha256' => hash('sha256', $prompt),
            'preview' => $preview,
            'tail_preview' => $tailPreview,
            'truncated' => $length > mb_strlen($preview),
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<int,string>
     */
    private function validateOutputPayload(AIContentJob $job, array $payload): array
    {
        return $this->resolveOutputValidator($job)->validate($job, $payload);
    }

    private function resolveOutputValidator(AIContentJob $job): AIOutputValidatorInterface
    {
        return app(match ($job->target_type) {
            AIContentTargetType::Quiz => QuizOutputValidator::class,
            AIContentTargetType::Assignment => AssignmentOutputValidator::class,
            AIContentTargetType::Summary => SummaryOutputValidator::class,
            AIContentTargetType::Flashcards => FlashcardsOutputValidator::class,
            AIContentTargetType::InteractiveActivity => InteractiveActivityOutputValidator::class,
        });
    }

    /**
     * @return array<string,string>|null
     */
    private function normalizeTranslations(mixed $value, ?string $fallback = null, string $language = 'ar'): ?array
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
                return match ($language) {
                    'en' => isset($translations['en'])
                        ? ['en' => $translations['en']]
                        : (isset($translations['ar']) ? ['en' => $translations['ar']] : null),
                    'both' => $translations,
                    default => isset($translations['ar'])
                        ? ['ar' => $translations['ar']]
                        : (isset($translations['en']) ? ['ar' => $translations['en']] : null),
                };
            }
        }

        if (is_string($value) && $value !== '') {
            return match ($language) {
                'en' => ['en' => $value],
                'both' => ['ar' => $value, 'en' => $value],
                default => ['ar' => $value],
            };
        }

        if (is_string($fallback) && $fallback !== '') {
            return match ($language) {
                'en' => ['en' => $fallback],
                'both' => ['ar' => $fallback, 'en' => $fallback],
                default => ['ar' => $fallback],
            };
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

    private function resolveQuizPublicationTarget(Quiz $target, User $publisher): Quiz
    {
        if (! $target->is_active) {
            return $target;
        }

        return $this->quizService->duplicate($target, $publisher);
    }

    private function resolveAssignmentPublicationTarget(Assignment $target, User $publisher): Assignment
    {
        if (! $target->is_active) {
            return $target;
        }

        /** @var Assignment $clone */
        $clone = $target->replicate(['created_by']);
        $clone->created_by = $publisher->id;
        $clone->is_active = false;
        $clone->save();

        return $clone;
    }

    private function resolveLearningAssetPublicationTarget(LearningAsset $target, User $publisher): LearningAsset
    {
        if (! $target->is_active && $target->status !== LearningAssetStatus::Published) {
            return $target;
        }

        /** @var LearningAsset $clone */
        $clone = $target->replicate(['created_by', 'published_by', 'published_at']);
        $clone->created_by = $publisher->id;
        $clone->updated_by = $publisher->id;
        $clone->published_by = null;
        $clone->published_at = null;
        $clone->status = LearningAssetStatus::Draft;
        $clone->is_active = false;
        $clone->save();

        return $clone;
    }

    /**
     * @param  array<int,mixed>  $questions
     */
    private function replaceQuizQuestions(Quiz $quiz, array $questions, AIContentJob $job): int
    {
        $existingQuestions = $quiz->questions()->withTrashed()->get();
        foreach ($existingQuestions as $existingQuestion) {
            $existingQuestion->answers()->delete();
            $existingQuestion->forceDelete();
        }

        $maxOrder = -1;
        $addedCount = 0;

        foreach ($questions as $questionData) {
            if (! is_array($questionData)) {
                continue;
            }

            $questionText = data_get($questionData, 'question');
            $options = data_get($questionData, 'options');

            if (! $this->hasLocalizableTextNode($questionText) || ! is_array($options) || $options === []) {
                continue;
            }

            $maxOrder++;
            $questionType = $this->detectQuestionType($options);
            $question = QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'question_translations' => $this->normalizeTranslations($questionText, 'Question', $job->language),
                'question_type' => $questionType,
                'explanation_translations' => $this->normalizeTranslations(data_get($questionData, 'explanation'), null, $job->language),
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
                    $optionText = data_get($option, 'text');
                    $isCorrect = (bool) data_get($option, 'is_correct', false);
                } else {
                    continue;
                }

                if (! $this->hasLocalizableTextNode($optionText)) {
                    continue;
                }

                QuizAnswer::query()->create([
                    'quiz_question_id' => $question->id,
                    'answer_translations' => $this->normalizeTranslations($optionText, 'Option', $job->language),
                    'is_correct' => $isCorrect,
                    'order_index' => $index,
                ]);
            }

            $addedCount++;
        }

        return $addedCount;
    }

    private function hasLocalizableTextNode(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (! is_array($value)) {
            return false;
        }

        foreach (['ar', 'en'] as $locale) {
            $localized = data_get($value, $locale);
            if (is_string($localized) && trim($localized) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int,mixed>  $options
     */
    private function detectQuestionType(array $options): int
    {
        $correctAnswers = collect($options)
            ->filter(static fn (mixed $option): bool => is_array($option) && (bool) data_get($option, 'is_correct', false))
            ->count();

        return $correctAnswers > 1 ? QuestionType::MultipleChoice->value : QuestionType::SingleChoice->value;
    }

    private function deactivateConflictingQuizzes(Quiz $quiz, AIContentJob $job): void
    {
        Quiz::query()
            ->where('center_id', $job->center_id)
            ->where('course_id', $job->course_id)
            ->where('attachable_type', $job->source_type->value)
            ->where('attachable_id', $job->source_id)
            ->whereKeyNot($quiz->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function deactivateConflictingAssignments(Assignment $assignment, AIContentJob $job): void
    {
        Assignment::query()
            ->where('center_id', $job->center_id)
            ->where('course_id', $job->course_id)
            ->where('attachable_type', $job->source_type->value)
            ->where('attachable_id', $job->source_id)
            ->whereKeyNot($assignment->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
