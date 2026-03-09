<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAttempt
 */
class QuizAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'attempt_number' => $this->attempt_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'score' => $this->score !== null ? (float) $this->score : null,
            'points_earned' => (float) $this->points_earned,
            'points_possible' => (float) $this->points_possible,
            'passed' => $this->passed,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
