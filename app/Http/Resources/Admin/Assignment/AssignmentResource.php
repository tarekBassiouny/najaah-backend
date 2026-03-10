<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Assignment;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assignment
 */
class AssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->translate('title'),
            'title_translations' => $this->title_translations,
            'description' => $this->translate('description'),
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'submission_types' => $this->submission_types,
            'max_points' => (float) $this->max_points,
            'passing_score' => (float) $this->passing_score,
            'is_group_assignment' => $this->is_group_assignment,
            'max_group_size' => $this->max_group_size,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'due_date' => $this->due_date?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'late_submission_allowed' => $this->late_submission_allowed,
            'available_from' => $this->available_from?->toIso8601String(),
            'available_until' => $this->available_until?->toIso8601String(),
            'order_index' => $this->order_index,
            'submissions_count' => $this->whenCounted('submissions', $this->submissions_count ?? $this->submissions()->count()),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
