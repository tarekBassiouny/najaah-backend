<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile\Assignment;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assignment
 */
class AssignmentListResource extends JsonResource
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
            'is_group_assignment' => $this->is_group_assignment,
            'max_points' => (float) $this->max_points,
            'passing_score' => (float) $this->passing_score,
            'is_required' => $this->is_required,
            'due_date' => $this->due_date?->toIso8601String(),
            'is_past_due' => $this->isPastDue(),
            'late_submission_allowed' => $this->late_submission_allowed,
            'is_available' => $this->isAvailable(),
            'can_submit' => $this->getAttribute('can_submit'),
            'submission_status' => $submission?->status->value,
            'submission_status_label' => $submission?->status->label(),
            'score' => $submission?->score !== null ? (float) $submission->score : null,
            'passed' => $submission?->passed,
        ];
    }
}
