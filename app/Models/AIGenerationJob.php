<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AIGenerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quiz_id
 * @property string $source_type
 * @property int $source_id
 * @property AIGenerationStatus $status
 * @property int $questions_requested
 * @property int $questions_generated
 * @property string|null $ai_provider
 * @property string|null $ai_model
 * @property string|null $prompt_used
 * @property array<array<string, mixed>>|null $generated_questions
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Quiz $quiz
 * @property-read User $creator
 */
class AIGenerationJob extends Model
{
    /** @use HasFactory<\Database\Factories\AIGenerationJobFactory> */
    use HasFactory;

    public const SOURCE_TYPE_VIDEO = 'video';

    public const SOURCE_TYPE_PDF = 'pdf';

    /** @var list<string> */
    protected $fillable = [
        'quiz_id',
        'source_type',
        'source_id',
        'status',
        'questions_requested',
        'questions_generated',
        'ai_provider',
        'ai_model',
        'prompt_used',
        'generated_questions',
        'error_message',
        'started_at',
        'completed_at',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => AIGenerationStatus::class,
        'questions_requested' => 'integer',
        'questions_generated' => 'integer',
        'generated_questions' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<Quiz, self> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSource(): Video|Pdf|null
    {
        return match ($this->source_type) {
            self::SOURCE_TYPE_VIDEO => Video::find($this->source_id),
            self::SOURCE_TYPE_PDF => Pdf::find($this->source_id),
            default => null,
        };
    }

    public function isPending(): bool
    {
        return $this->status === AIGenerationStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === AIGenerationStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === AIGenerationStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === AIGenerationStatus::Failed;
    }

    public function hasGeneratedQuestions(): bool
    {
        return $this->generated_questions !== null && count($this->generated_questions) > 0;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AIGenerationStatus::Pending);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', AIGenerationStatus::Processing);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AIGenerationStatus::Completed);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', AIGenerationStatus::Failed);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForQuiz(Builder $query, int $quizId): Builder
    {
        return $query->where('quiz_id', $quizId);
    }
}
