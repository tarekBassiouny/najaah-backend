<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Assignment;

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
        $submission = $this->getAttribute('my_submission');

        return [
            'id' => $this->id,
            'title' => $this->translate('title'),
            'description' => $this->translate('description'),
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
            'due_date' => $this->due_date?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'is_late' => $this->getAttribute('is_late'),
            'late_submission_allowed' => $this->late_submission_allowed,
            'late_penalty_percent' => (float) $this->late_penalty_percent,
            'available_from' => $this->available_from?->toIso8601String(),
            'available_until' => $this->available_until?->toIso8601String(),
            'is_available' => $this->isAvailable(),
            'can_submit' => $this->getAttribute('can_submit'),
            'my_submission' => $submission ? new AssignmentSubmissionResource($submission) : null,
        ];
    }
}
