<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\LearningAssetStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class LearningAssetAdminService
{
    /**
     * @param  array<string,mixed>  $filters
     * @return Collection<int, LearningAsset>
     */
    public function listForCourse(Center $center, Course $course, array $filters = []): Collection
    {
        $query = LearningAsset::query()
            ->where('center_id', $center->id)
            ->where('course_id', $course->id)
            ->latest('id');

        if (isset($filters['attachable_type']) && is_string($filters['attachable_type'])) {
            $query->where('attachable_type', $filters['attachable_type']);
        }

        if (isset($filters['attachable_id']) && is_numeric($filters['attachable_id'])) {
            $query->where('attachable_id', (int) $filters['attachable_id']);
        }

        if (isset($filters['asset_type']) && is_string($filters['asset_type'])) {
            $query->where('asset_type', $filters['asset_type']);
        }

        if (isset($filters['status']) && is_numeric($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        return $query->get();
    }

    public function getInCenter(Center $center, LearningAsset $asset): LearningAsset
    {
        abort_unless($asset->center_id === $center->id, 404, 'Learning asset not found in this center.');

        return $asset;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function update(LearningAsset $asset, array $data, User $admin): LearningAsset
    {
        $asset->update([
            'title_translations' => $data['title_translations'] ?? $asset->title_translations,
            'content_translations' => $data['content_translations'] ?? $asset->content_translations,
            'payload' => $data['payload'] ?? $asset->payload,
            'updated_by' => $admin->id,
        ]);

        return $asset->fresh() ?? $asset;
    }

    public function updateStatus(LearningAsset $asset, LearningAssetStatus $status, User $admin): LearningAsset
    {
        return DB::transaction(function () use ($asset, $status, $admin): LearningAsset {
            if ($status === LearningAssetStatus::Published) {
                LearningAsset::query()
                    ->where('center_id', $asset->center_id)
                    ->where('course_id', $asset->course_id)
                    ->where('attachable_type', $asset->attachable_type)
                    ->where('attachable_id', $asset->attachable_id)
                    ->where('asset_type', $asset->asset_type)
                    ->whereKeyNot($asset->id)
                    ->where('status', LearningAssetStatus::Published)
                    ->update([
                        'status' => LearningAssetStatus::Archived,
                        'is_active' => false,
                        'updated_by' => $admin->id,
                    ]);
            }

            $asset->update([
                'status' => $status,
                'is_active' => $status === LearningAssetStatus::Published,
                'published_by' => $status === LearningAssetStatus::Published ? $admin->id : $asset->published_by,
                'published_at' => $status === LearningAssetStatus::Published ? now() : $asset->published_at,
                'updated_by' => $admin->id,
            ]);

            return $asset->fresh() ?? $asset;
        });
    }
}
