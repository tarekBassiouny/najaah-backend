<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentTargetType;
use App\Enums\LearningAssetStatus;
use App\Enums\LearningAssetType;
use App\Enums\TextExtractionStatus;
use App\Models\AIContentJob;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Quiz;
use Illuminate\Support\Collection;

final class CourseAssetCatalogService
{
    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function build(Course $course, array $filters = []): array
    {
        $course->loadMissing(['sections', 'videos', 'pdfs']);

        $sections = $course->sections->sortBy('order_index')->values();
        $sectionMap = $sections->mapWithKeys(fn ($section): array => [$section->id => $section]);

        $sources = collect()
            ->merge($this->mapSources($course->videos, 'video', $sectionMap))
            ->merge($this->mapSources($course->pdfs, 'pdf', $sectionMap));

        if (isset($filters['section_id']) && is_numeric($filters['section_id'])) {
            $sectionId = (int) $filters['section_id'];
            $sources = $sources->filter(fn (array $source): bool => (int) ($source['section']['id'] ?? 0) === $sectionId);
        }

        if (isset($filters['source_type']) && is_string($filters['source_type'])) {
            $sourceType = $filters['source_type'];
            $sources = $sources->filter(fn (array $source): bool => $source['type'] === $sourceType);
        }

        if (isset($filters['source_id']) && is_numeric($filters['source_id'])) {
            $sourceId = (int) $filters['source_id'];
            $sources = $sources->filter(fn (array $source): bool => $source['id'] === $sourceId);
        }

        $sourceKeys = $sources->map(fn (array $source): string => $this->sourceKey($source['type'], $source['id']))->all();

        $quizzes = Quiz::query()
            ->where('course_id', $course->id)
            ->whereIn('attachable_type', ['video', 'pdf'])
            ->get()
            ->groupBy(fn (Quiz $quiz): string => $this->sourceKey((string) $quiz->attachable_type, (int) $quiz->attachable_id));

        $assignments = Assignment::query()
            ->where('course_id', $course->id)
            ->whereIn('attachable_type', ['video', 'pdf'])
            ->get()
            ->groupBy(fn (Assignment $assignment): string => $this->sourceKey((string) $assignment->attachable_type, (int) $assignment->attachable_id));

        $learningAssets = LearningAsset::query()
            ->where('course_id', $course->id)
            ->whereIn('attachable_type', ['video', 'pdf'])
            ->whereIn('asset_type', [LearningAssetType::Summary, LearningAssetType::Flashcards])
            ->get()
            ->groupBy(fn (LearningAsset $asset): string => $this->sourceKey((string) $asset->attachable_type, (int) $asset->attachable_id));

        $sourceKeyLookup = array_fill_keys($sourceKeys, true);

        $jobs = AIContentJob::query()
            ->where('course_id', $course->id)
            ->whereIn('source_type', ['video', 'pdf'])
            ->whereIn('target_type', [
                AIContentTargetType::Summary,
                AIContentTargetType::Quiz,
                AIContentTargetType::Flashcards,
                AIContentTargetType::Assignment,
            ])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (AIContentJob $job): bool => isset($sourceKeyLookup[$this->sourceKey($job->source_type->value, $job->source_id)]))
            ->groupBy(fn (AIContentJob $job): string => $this->sourceKey($job->source_type->value, $job->source_id));

        $slots = ['summary', 'quiz', 'flashcards', 'assignment'];

        $sourcePayload = $sources
            ->sortBy([
                fn (array $source): int => (int) ($source['section']['order_index'] ?? PHP_INT_MAX),
                fn (array $source): int => $source['order_index'],
                fn (array $source): string => $source['type'],
            ])
            ->values()
            ->map(function (array $source) use ($slots, $quizzes, $assignments, $learningAssets, $jobs): array {
                $sourceKey = $this->sourceKey($source['type'], $source['id']);

                return [
                    ...$source,
                    'assets' => collect($slots)->map(fn (string $assetType): array => $this->buildSlot(
                        $assetType,
                        $quizzes->get($sourceKey, collect()),
                        $assignments->get($sourceKey, collect()),
                        $learningAssets->get($sourceKey, collect()),
                        $jobs->get($sourceKey, collect())
                    ))->values()->all(),
                ];
            })
            ->all();

        return [
            'course' => [
                'id' => $course->id,
                'title' => $course->translate('title'),
            ],
            'sources' => $sourcePayload,
        ];
    }

    /**
     * @param  Collection<int,mixed>  $items
     * @param  Collection<int,mixed>  $sectionMap
     * @return Collection<int,array<string,mixed>>
     */
    private function mapSources(Collection $items, string $type, Collection $sectionMap): Collection
    {
        return $items->map(function ($item) use ($type, $sectionMap): array {
            $sectionId = is_numeric($item->pivot->section_id ?? null) ? (int) $item->pivot->section_id : null;
            $section = $sectionId !== null ? $sectionMap->get($sectionId) : null;

            /** @var array<string,mixed> $payload */
            $payload = [
                'type' => $type,
                'id' => (int) $item->id,
                'title' => $item->translate('title'),
                'order_index' => (int) ($item->pivot->order_index ?? 0),
                'ai_readiness' => $this->resolveSourceReadiness($type, $item),
                'section' => $section === null ? null : [
                    'id' => (int) $section->id,
                    'title' => $section->translate('title'),
                    'order_index' => (int) $section->order_index,
                ],
            ];

            return $payload;
        });
    }

    /**
     * @param  Collection<int,Quiz>  $quizzes
     * @param  Collection<int,Assignment>  $assignments
     * @param  Collection<int,LearningAsset>  $learningAssets
     * @param  Collection<int,AIContentJob>  $jobs
     * @return array<string,mixed>
     */
    private function buildSlot(
        string $assetType,
        Collection $quizzes,
        Collection $assignments,
        Collection $learningAssets,
        Collection $jobs
    ): array {
        $canonical = match ($assetType) {
            'quiz' => $this->selectQuiz($quizzes),
            'assignment' => $this->selectAssignment($assignments),
            'summary' => $this->selectLearningAsset($learningAssets, LearningAssetType::Summary),
            'flashcards' => $this->selectLearningAsset($learningAssets, LearningAssetType::Flashcards),
            default => null,
        };

        $latestJob = $jobs->first(fn (AIContentJob $job): bool => $job->target_type->value === $assetType);

        return [
            'asset_type' => $assetType,
            'slot_state' => $this->resolveSlotState($canonical, $latestJob),
            'canonical' => $canonical,
            'latest_job' => $latestJob === null ? null : [
                'id' => $latestJob->id,
                'batch_key' => $latestJob->batch_key,
                'status' => $latestJob->status->value,
                'status_label' => $latestJob->status->label(),
                'error_message' => $latestJob->error_message,
            ],
        ];
    }

    /**
     * @param  Collection<int,Quiz>  $quizzes
     * @return array<string,mixed>|null
     */
    private function selectQuiz(Collection $quizzes): ?array
    {
        /** @var Quiz|null $quiz */
        $quiz = $quizzes
            ->sortByDesc(fn (Quiz $quiz): string => sprintf('%d-%010d', $quiz->is_active ? 1 : 0, $quiz->id))
            ->first();

        if (! $quiz instanceof Quiz) {
            return null;
        }

        return [
            'id' => $quiz->id,
            'kind' => 'quiz',
            'title' => $quiz->translate('title'),
            'is_active' => $quiz->is_active,
            'updated_at' => $quiz->updated_at->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int,Assignment>  $assignments
     * @return array<string,mixed>|null
     */
    private function selectAssignment(Collection $assignments): ?array
    {
        /** @var Assignment|null $assignment */
        $assignment = $assignments
            ->sortByDesc(fn (Assignment $assignment): string => sprintf('%d-%010d', $assignment->is_active ? 1 : 0, $assignment->id))
            ->first();

        if (! $assignment instanceof Assignment) {
            return null;
        }

        return [
            'id' => $assignment->id,
            'kind' => 'assignment',
            'title' => $assignment->translate('title'),
            'is_active' => $assignment->is_active,
            'updated_at' => $assignment->updated_at->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int,LearningAsset>  $assets
     * @return array<string,mixed>|null
     */
    private function selectLearningAsset(Collection $assets, LearningAssetType $type): ?array
    {
        /** @var LearningAsset|null $asset */
        $asset = $assets
            ->filter(fn (LearningAsset $asset): bool => $asset->asset_type === $type)
            ->sortByDesc(
                fn (LearningAsset $asset): string => sprintf(
                    '%d-%010d',
                    $asset->status === LearningAssetStatus::Published && $asset->is_active ? 1 : 0,
                    $asset->id
                )
            )
            ->first();

        if (! $asset instanceof LearningAsset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'kind' => 'learning_asset',
            'title' => $asset->translate('title'),
            'status' => $asset->status->value,
            'status_label' => $asset->status->label(),
            'is_active' => $asset->is_active,
            'updated_at' => $asset->updated_at->toIso8601String(),
        ];
    }

    /**
     * @param  array<string,mixed>|null  $canonical
     */
    private function resolveSlotState(?array $canonical, ?AIContentJob $latestJob): string
    {
        if ($latestJob instanceof AIContentJob) {
            return match ($latestJob->status) {
                AIContentJobStatus::Pending, AIContentJobStatus::Processing => 'generating',
                AIContentJobStatus::Completed => 'review_required',
                AIContentJobStatus::Approved => 'approved',
                AIContentJobStatus::Failed => 'failed',
                default => $this->resolveCanonicalState($canonical),
            };
        }

        return $this->resolveCanonicalState($canonical);
    }

    /**
     * @param  array<string,mixed>|null  $canonical
     */
    private function resolveCanonicalState(?array $canonical): string
    {
        if ($canonical === null) {
            return 'missing';
        }

        return ($canonical['is_active'] ?? false) ? 'published' : 'draft';
    }

    private function sourceKey(string $type, int $id): string
    {
        return sprintf('%s:%d', $type, $id);
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveSourceReadiness(string $type, object $item): array
    {
        return match ($type) {
            'video' => $this->resolveVideoReadiness($item),
            'pdf' => $this->resolvePdfReadiness($item),
            default => [
                'is_ready' => true,
                'code' => 'READY',
                'badge' => 'Ready',
                'title' => null,
                'message' => null,
            ],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveVideoReadiness(object $item): array
    {
        /** @var mixed $transcript */
        $transcript = data_get($item, 'transcript');

        if (is_string($transcript) && trim($transcript) !== '') {
            return [
                'is_ready' => true,
                'code' => 'READY',
                'badge' => 'Transcript Ready',
                'title' => null,
                'message' => null,
            ];
        }

        return [
            'is_ready' => false,
            'code' => 'TRANSCRIPT_REQUIRED',
            'badge' => 'Transcript Missing',
            'title' => 'Transcript required',
            'message' => 'This video is not AI-ready yet. Add a transcript from the Videos page before generating assets.',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function resolvePdfReadiness(object $item): array
    {
        $status = data_get($item, 'text_extraction_status');
        if (! $status instanceof TextExtractionStatus) {
            $status = null;
        }

        $textContent = data_get($item, 'text_content');
        if (
            $status === TextExtractionStatus::Completed
            && is_string($textContent)
            && trim($textContent) !== ''
        ) {
            return [
                'is_ready' => true,
                'code' => 'READY',
                'badge' => 'Extraction Ready',
                'title' => null,
                'message' => null,
            ];
        }

        return match ($status) {
            TextExtractionStatus::Pending => [
                'is_ready' => false,
                'code' => 'PDF_NOT_READY',
                'badge' => 'Extraction Pending',
                'title' => 'PDF extraction pending',
                'message' => 'This PDF is not AI-ready yet. Wait for text extraction to complete before generating assets.',
            ],
            TextExtractionStatus::Processing => [
                'is_ready' => false,
                'code' => 'PDF_NOT_READY',
                'badge' => 'Extraction Processing',
                'title' => 'PDF extraction in progress',
                'message' => 'This PDF is not AI-ready yet. Wait for text extraction to complete before generating assets.',
            ],
            TextExtractionStatus::Failed,
            TextExtractionStatus::Skipped => [
                'is_ready' => false,
                'code' => 'PDF_TEXT_EXTRACTION_FAILED',
                'badge' => 'Extraction Failed',
                'title' => 'PDF extraction failed',
                'message' => 'This PDF is not AI-ready yet. Re-upload it or fix text extraction before generating assets.',
            ],
            default => [
                'is_ready' => false,
                'code' => 'PDF_NOT_READY',
                'badge' => 'Extraction Pending',
                'title' => 'PDF extraction pending',
                'message' => 'This PDF is not AI-ready yet. Wait for text extraction to complete before generating assets.',
            ],
        };
    }
}
