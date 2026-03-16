<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\LearningAsset;

use App\Models\Course;
use App\Models\LearningAsset;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LearningAsset
 */
class LearningAssetResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'course_id' => $this->course_id,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'attachable_label' => $this->resolveAttachableLabel(),
            'asset_type' => $this->asset_type->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'title' => $this->translate('title'),
            'title_translations' => $this->title_translations,
            'content' => $this->translate('content'),
            'content_translations' => $this->content_translations,
            'payload' => $this->payload,
            'is_active' => $this->is_active,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    private function resolveAttachableLabel(): ?string
    {
        if (! is_string($this->attachable_type) || ! is_numeric($this->attachable_id)) {
            return null;
        }

        return match ($this->attachable_type) {
            'video' => $this->resolveTranslatableTitle(Video::query()->find((int) $this->attachable_id)),
            'pdf' => $this->resolveTranslatableTitle(Pdf::query()->find((int) $this->attachable_id)),
            'section' => $this->resolveTranslatableTitle(Section::query()->find((int) $this->attachable_id)),
            'course' => $this->resolveTranslatableTitle(Course::query()->find((int) $this->attachable_id)),
            default => null,
        };
    }

    private function resolveTranslatableTitle(?object $model): ?string
    {
        if ($model === null || ! method_exists($model, 'translate')) {
            return null;
        }

        return $model->translate('title');
    }
}
