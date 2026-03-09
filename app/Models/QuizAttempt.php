<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuizAttemptStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $quiz_id
 * @property int $user_id
 * @property int $enrollment_id
 * @property int $center_id
 * @property int $attempt_number
 * @property QuizAttemptStatus $status
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property int $time_spent_seconds
 * @property float|null $score
 * @property float $points_earned
 * @property float $points_possible
 * @property bool $passed
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Quiz $quiz
 * @property-read User $user
 * @property-read Enrollment $enrollment
 * @property-read Center $center
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuizAttemptAnswer> $answers
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'quiz_id',
        'user_id',
        'enrollment_id',
        'center_id',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'time_spent_seconds',
        'score',
        'points_earned',
        'points_possible',
        'passed',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'attempt_number' => 'integer',
        'status' => QuizAttemptStatus::class,
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'score' => 'decimal:2',
        'points_earned' => 'decimal:2',
        'points_possible' => 'decimal:2',
        'passed' => 'boolean',
    ];

    /** @return BelongsTo<Quiz, self> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Enrollment, self> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return HasMany<QuizAttemptAnswer, self> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === QuizAttemptStatus::InProgress;
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isTimedOut(): bool
    {
        return $this->status === QuizAttemptStatus::TimedOut;
    }

    public function hasTimeLimitExpired(): bool
    {
        if ($this->started_at === null) {
            return false;
        }

        $quiz = $this->quiz;
        if (! $quiz->hasTimeLimit()) {
            return false;
        }

        return now()->diffInMinutes($this->started_at) >= $quiz->time_limit_minutes;
    }

    public function getRemainingTimeSeconds(): ?int
    {
        if ($this->started_at === null || ! $this->quiz->hasTimeLimit()) {
            return null;
        }

        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        $timeLimitSeconds = $this->quiz->time_limit_minutes * 60;

        return max(0, $timeLimitSeconds - $elapsedSeconds);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', QuizAttemptStatus::InProgress);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', [
            QuizAttemptStatus::Submitted,
            QuizAttemptStatus::TimedOut,
            QuizAttemptStatus::Graded,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('passed', true);
    }
}
