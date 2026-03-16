<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Models\VideoCodeBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VideoCodeBatch
 */
class VideoCodeBatchListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['video', 'course', 'generator', 'closer']);

        return [
            'id' => $this->id,
            'batch_code' => $this->batch_code,
            'video_id' => $this->video_id,
            'video_title' => $this->video->translate('title'),
            'course_id' => $this->course_id,
            'course_title' => $this->course->translate('title'),
            'quantity' => $this->quantity,
            'sold_limit' => $this->sold_limit,
            'redeemed_count' => $this->redeemed_count,
            'view_limit_per_code' => $this->view_limit_per_code,
            'status' => strtolower($this->status->name),
            'status_label' => $this->status->name,
            'is_open' => $this->isOpen(),
            'can_expand' => $this->isOpen(),
            'can_close' => $this->canClose(),
            'available_codes' => $this->quantity - $this->redeemed_count,
            'remaining_redemptions' => $this->sold_limit !== null
                ? max(0, $this->sold_limit - $this->redeemed_count)
                : null,
            'generated_by' => $this->generator ? [
                'id' => $this->generator->id,
                'name' => $this->generator->name,
            ] : null,
            'generated_at' => $this->generated_at->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by' => $this->closer ? [
                'id' => $this->closer->id,
                'name' => $this->closer->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function canClose(): bool
    {
        if ($this->isOpen()) {
            return true;
        }

        return $this->sold_limit === null || $this->sold_limit < $this->quantity;
    }
}
