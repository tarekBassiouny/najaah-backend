<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Enrollment;
use App\Models\LearningAssetProgress;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Services\Courses\CourseThumbnailUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin Enrollment
 */
class StudentEnrollmentResource extends JsonResource
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
        /** @var Enrollment $enrollment */
        $enrollment = $this->resource;
        $course = $enrollment->course;
        $thumbnailUrlResolver = app(CourseThumbnailUrlResolver::class);

        if ($course === null) {
            return [
                'id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at->toISOString(),
                'expires_at' => $enrollment->expires_at?->toISOString(),
                'status' => $enrollment->status->value,
                'status_label' => $enrollment->statusLabel(),
                'progress_percentage' => 0.0,
                'course' => null,
            ];
        }

        // Collect all videos from all sections
        $videos = $course->sections->flatMap(fn ($section) => $section->videos);
        $videoCount = $videos->count();
        $learningAssets = $course->relationLoaded('learningAssets')
            ? $course->learningAssets
            : collect();
        $learningAssetCount = $learningAssets->count();

        // Build video resources with context and collect progress data
        $videoProgressData = [];
        $videoResources = $videos->map(function ($video) use ($course, &$videoProgressData): \App\Http\Resources\Admin\StudentCourseVideoResource {
            $resource = new StudentCourseVideoResource($video);
            if ($this->student !== null && $this->playbackSessions !== null) {
                $resource->setContext($this->student, $course, $this->playbackSessions);

                // Get progress for this video to calculate enrollment progress
                $videoSessions = $this->playbackSessions->where('video_id', $video->id);
                $latestSession = $videoSessions->sortByDesc('id')->first();
                $videoProgressData[] = $latestSession?->progress_percent ?? 0;
            }

            return $resource;
        });

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

        // Calculate enrollment progress as average of all trackable course content
        $progressPercentage = $trackableProgressData !== []
            ? round(array_sum($trackableProgressData) / count($trackableProgressData), 1)
            : 0.0;

        return [
            'id' => $enrollment->id,
            'enrolled_at' => $enrollment->enrolled_at->toISOString(),
            'expires_at' => $enrollment->expires_at?->toISOString(),
            'status' => $enrollment->status->value,
            'status_label' => $enrollment->statusLabel(),
            'progress_percentage' => $progressPercentage,
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
}
