<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningAssetProgress;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\VideoAccess;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentCourseAccessResource extends JsonResource
{
    private ?User $student = null;

    /** @var Collection<int, PlaybackSession>|null */
    private ?Collection $playbackSessions = null;

    /** @var Collection<int, LearningAssetProgress>|null */
    private ?Collection $learningAssetProgressRecords = null;

    /**
     * @param  Collection<int, PlaybackSession>  $playbackSessions
     * @param  Collection<int, LearningAssetProgress>  $learningAssetProgressRecords
     */
    public function setContext(User $student, Collection $playbackSessions, Collection $learningAssetProgressRecords): self
    {
        $this->student = $student;
        $this->playbackSessions = $playbackSessions;
        $this->learningAssetProgressRecords = $learningAssetProgressRecords;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($this->resource) ? $this->resource : [];

        /** @var Course|null $course */
        $course = $payload['course'] ?? null;
        /** @var Enrollment|null $enrollment */
        $enrollment = $payload['enrollment'] ?? null;
        /** @var Collection<int, VideoAccess> $videoAccesses */
        $videoAccesses = $payload['video_accesses'] ?? collect();

        if (! $course instanceof Course) {
            return [
                'access_type' => $payload['access_type'] ?? null,
                'access_sources' => $payload['access_sources'] ?? [],
                'has_access' => false,
                'granted_at' => null,
                'last_activity_at' => null,
                'progress_percentage' => 0.0,
                'enrollment' => null,
                'video_code_access' => null,
                'course' => null,
            ];
        }

        $thumbnailUrlResolver = app(CourseThumbnailUrlResolver::class);
        $videos = $course->sections->flatMap(fn ($section) => $section->videos);
        $videoCount = $videos->count();
        $learningAssets = $course->relationLoaded('learningAssets')
            ? $course->learningAssets
            : collect();
        $learningAssetCount = $learningAssets->count();

        $coursePlaybackSessions = $this->playbackSessions
            ? $this->playbackSessions->where('course_id', $course->id)
            : collect();

        $videoProgressData = [];
        $videoResources = $videos->map(function ($video) use ($course, $coursePlaybackSessions, &$videoProgressData): array {
            $resource = new StudentCourseVideoResource($video);

            if ($this->student !== null) {
                $resource->setContext($this->student, $course, $coursePlaybackSessions);

                $videoSessions = $coursePlaybackSessions->where('video_id', $video->id);
                $latestSession = $videoSessions->sortByDesc('id')->first();
                $videoProgressData[] = $latestSession?->progress_percent ?? 0;
            }

            return $resource->resolve();
        })->values()->all();

        $learningAssetProgressRecords = $this->learningAssetProgressRecords
            ? $this->learningAssetProgressRecords->where('course_id', $course->id)->keyBy('learning_asset_id')
            : collect();

        $learningAssetProgressData = $learningAssets->map(function ($asset) use ($learningAssetProgressRecords): int {
            /** @var LearningAssetProgress|null $progress */
            $progress = $learningAssetProgressRecords->get($asset->id);

            return $progress?->progress_percent ?? 0;
        })->all();

        $learningAssetsCompleted = $learningAssets->filter(function ($asset) use ($learningAssetProgressRecords): bool {
            /** @var LearningAssetProgress|null $progress */
            $progress = $learningAssetProgressRecords->get($asset->id);

            return $progress?->completed_at !== null;
        })->count();

        $learningAssetsInProgress = $learningAssets->filter(function ($asset) use ($learningAssetProgressRecords): bool {
            /** @var LearningAssetProgress|null $progress */
            $progress = $learningAssetProgressRecords->get($asset->id);

            return $progress !== null && $progress->completed_at === null;
        })->count();

        $trackableProgressData = [...$videoProgressData, ...$learningAssetProgressData];
        $progressPercentage = $trackableProgressData !== []
            ? round(array_sum($trackableProgressData) / count($trackableProgressData), 1)
            : 0.0;

        $courseLastActivity = $this->resolveCourseLastActivity($course->id, $coursePlaybackSessions, $learningAssetProgressRecords);
        $videoAccessTotalViewLimit = $videoAccesses->pluck('total_view_limit')
            ->filter(static fn ($limit): bool => is_numeric($limit))
            ->sum();

        return [
            'access_type' => $payload['access_type'] ?? null,
            'access_sources' => $payload['access_sources'] ?? [],
            'has_access' => true,
            'granted_at' => $this->resolveGrantedAt($enrollment, $videoAccesses),
            'last_activity_at' => $courseLastActivity,
            'progress_percentage' => $progressPercentage,
            'enrollment' => $enrollment instanceof Enrollment ? [
                'id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                'expires_at' => $enrollment->expires_at?->toISOString(),
                'status' => $enrollment->status->value,
                'status_label' => $enrollment->statusLabel(),
            ] : null,
            'video_code_access' => $videoAccesses->isNotEmpty() ? [
                'active_video_access_count' => $videoAccesses->count(),
                'granted_videos_count' => $videoAccesses->pluck('video_id')->unique()->count(),
                'total_view_limit' => $videoAccessTotalViewLimit > 0 ? $videoAccessTotalViewLimit : null,
                'granted_at' => $videoAccesses->pluck('granted_at')->filter()->sort()->first()?->toISOString(),
            ] : null,
            'course' => [
                'id' => $course->id,
                'title' => $course->translate('title'),
                'title_translations' => $course->title_translations,
                'description' => $course->translate('description'),
                'description_translations' => $course->description_translations,
                'thumbnail' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
                'thumbnail_url' => $thumbnailUrlResolver->resolve($course->thumbnail_url),
                'status' => $course->status->value,
                'status_key' => Str::snake($course->status->name),
                'status_label' => $course->status->name,
                'access_model' => $course->access_model->value,
                'is_published' => (bool) $course->is_published,
                'video_count' => $videoCount,
                'learning_asset_count' => $learningAssetCount,
                'learning_assets_progress' => [
                    'total' => $learningAssetCount,
                    'completed' => $learningAssetsCompleted,
                    'in_progress' => $learningAssetsInProgress,
                    'not_started' => max(0, $learningAssetCount - $learningAssetsCompleted - $learningAssetsInProgress),
                    'progress_percentage' => $learningAssetCount > 0
                        ? round(array_sum($learningAssetProgressData) / $learningAssetCount, 1)
                        : 0.0,
                ],
                'videos' => $videoResources,
            ],
        ];
    }

    /**
     * @param  Collection<int, PlaybackSession>  $coursePlaybackSessions
     * @param  Collection<int, LearningAssetProgress>  $courseLearningAssetProgress
     */
    private function resolveCourseLastActivity(
        int $courseId,
        Collection $coursePlaybackSessions,
        Collection $courseLearningAssetProgress
    ): ?string {
        $timestamps = [];

        $latestPlayback = $coursePlaybackSessions->sortByDesc('updated_at')->first();
        if ($latestPlayback !== null && $latestPlayback->updated_at !== null) {
            $timestamps[] = $latestPlayback->updated_at;
        }

        /** @var LearningAssetProgress|null $latestProgress */
        $latestProgress = $courseLearningAssetProgress
            ->where('course_id', $courseId)
            ->sortByDesc('last_interacted_at')
            ->first();

        if ($latestProgress !== null && $latestProgress->last_interacted_at !== null) {
            $timestamps[] = $latestProgress->last_interacted_at;
        }

        if ($timestamps === []) {
            return null;
        }

        return max($timestamps)->toISOString();
    }

    /**
     * @param  Collection<int, VideoAccess>  $videoAccesses
     */
    private function resolveGrantedAt(?Enrollment $enrollment, Collection $videoAccesses): ?string
    {
        if ($enrollment instanceof Enrollment) {
            return $enrollment->enrolled_at->toISOString();
        }

        return $videoAccesses->pluck('granted_at')->filter()->sort()->first()?->toISOString();
    }
}
