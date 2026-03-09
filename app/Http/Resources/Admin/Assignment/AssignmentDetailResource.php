<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Assignment;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assignment
 */
class AssignmentDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'course_id' => $this->course_id,
            'title' => $this->translate('title'),
            'title_translations' => $this->title_translations,
            'description' => $this->translate('description'),
            'description_translations' => $this->description_translations,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'submission_types' => $this->submission_types,
            'allowed_file_types' => $this->allowed_file_types,
            'max_file_size_mb' => $this->max_file_size_mb,
            'max_files' => $this->max_files,
            'is_group_assignment' => $this->is_group_assignment,
            'max_group_size' => $this->max_group_size,
            'max_points' => (float) $this->max_points,
            'passing_score' => (float) $this->passing_score,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'is_available' => $this->isAvailable(),
            'due_date' => $this->due_date?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'late_submission_allowed' => $this->late_submission_allowed,
            'late_penalty_percent' => (float) $this->late_penalty_percent,
            'available_from' => $this->available_from?->toIso8601String(),
            'available_until' => $this->available_until?->toIso8601String(),
            'order_index' => $this->order_index,
            'submissions_count' => $this->submissions()->count(),
            'pending_grading_count' => $this->submissions()->submitted()->count(),
            'submissions' => AssignmentSubmissionResource::collection($this->whenLoaded('submissions')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
