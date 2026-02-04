<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgentExecutionStatus;
use App\Enums\AgentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property AgentType $agent_type
 * @property string|null $target_type
 * @property int|null $target_id
 * @property AgentExecutionStatus $status
 * @property array<string, mixed>|null $context
 * @property array<string, mixed>|null $result
 * @property array<int, string>|null $steps_completed
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $initiated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Center $center
 * @property-read User $initiator
 * @property-read Model|null $target
 */
final class AgentExecution extends Model
{
    /** @use HasFactory<\Database\Factories\AgentExecutionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'agent_type',
        'target_type',
        'target_id',
        'status',
        'context',
        'result',
        'steps_completed',
        'started_at',
        'completed_at',
        'initiated_by',
    ];

    protected $casts = [
        'agent_type' => AgentType::class,
        'status' => AgentExecutionStatus::class,
        'context' => 'array',
        'result' => 'array',
        'steps_completed' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return array<int, string> */
    public static function statusLabels(): array
    {
        return [
            0 => 'PENDING',
            1 => 'RUNNING',
            2 => 'COMPLETED',
            3 => 'FAILED',
        ];
    }

    public function statusLabel(): string
    {
        if ($this->status instanceof AgentExecutionStatus) {
            return match ($this->status) {
                AgentExecutionStatus::Pending => 'PENDING',
                AgentExecutionStatus::Running => 'RUNNING',
                AgentExecutionStatus::Completed => 'COMPLETED',
                AgentExecutionStatus::Failed => 'FAILED',
            };
        }

        return self::statusLabels()[(int) $this->status] ?? 'UNKNOWN';
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /** @return MorphTo<Model, self> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by agent type.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, AgentType $type): Builder
    {
        return $query->where('agent_type', $type->value);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithStatus(Builder $query, AgentExecutionStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter pending executions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AgentExecutionStatus::Pending->value);
    }

    /**
     * Scope to filter running executions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', AgentExecutionStatus::Running->value);
    }

    /**
     * Scope to filter completed executions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AgentExecutionStatus::Completed->value);
    }

    /**
     * Scope to filter failed executions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', AgentExecutionStatus::Failed->value);
    }

    /**
     * Scope to filter by center.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Mark execution as running.
     */
    public function markAsRunning(): self
    {
        $this->status = AgentExecutionStatus::Running;
        $this->started_at = now();
        $this->save();

        return $this;
    }

    /**
     * Mark execution as completed.
     *
     * @param  array<string, mixed>|null  $result
     */
    public function markAsCompleted(?array $result = null): self
    {
        $this->status = AgentExecutionStatus::Completed;
        $this->completed_at = now();
        $this->result = $result;
        $this->save();

        return $this;
    }

    /**
     * Mark execution as failed.
     *
     * @param  array<string, mixed>|null  $result
     */
    public function markAsFailed(?array $result = null): self
    {
        $this->status = AgentExecutionStatus::Failed;
        $this->completed_at = now();
        $this->result = $result;
        $this->save();

        return $this;
    }

    /**
     * Add a completed step.
     */
    public function addCompletedStep(string $step): self
    {
        $steps = $this->steps_completed ?? [];
        $steps[] = $step;
        $this->steps_completed = $steps;
        $this->save();

        return $this;
    }

    /**
     * Check if execution is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === AgentExecutionStatus::Running
            || $this->status === AgentExecutionStatus::Pending;
    }

    /**
     * Check if execution is finished (completed or failed).
     */
    public function isFinished(): bool
    {
        return $this->status === AgentExecutionStatus::Completed
            || $this->status === AgentExecutionStatus::Failed;
    }
}
