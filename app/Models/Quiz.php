<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AIContentTargetType;
use App\Enums\AttemptScorePolicy;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property int $course_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property string|null $attachable_type
 * @property int|null $attachable_id
 * @property float $passing_score
 * @property int $max_attempts
 * @property AttemptScorePolicy $attempt_score_policy
 * @property int|null $time_limit_minutes
 * @property bool $shuffle_questions
 * @property bool $shuffle_answers
 * @property bool $show_correct_answers
 * @property bool $show_score_immediately
 * @property bool $is_required
 * @property bool $is_active
 * @property \Carbon\Carbon|null $available_from
 * @property \Carbon\Carbon|null $available_until
 * @property int $order_index
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Center $center
 * @property-read Course $course
 * @property-read User $creator
 * @property-read Model|null $attachable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuizQuestion> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuizAttempt> $attempts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AIContentJob> $aiContentJobs
 */
class Quiz extends Model
{
    /** @use HasFactory<\Database\Factories\QuizFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'center_id',
        'course_id',
        'title_translations',
        'description_translations',
        'attachable_type',
        'attachable_id',
        'passing_score',
        'max_attempts',
        'attempt_score_policy',
        'time_limit_minutes',
        'shuffle_questions',
        'shuffle_answers',
        'show_correct_answers',
        'show_score_immediately',
        'is_required',
        'is_active',
        'available_from',
        'available_until',
        'order_index',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'passing_score' => 'decimal:2',
        'max_attempts' => 'integer',
        'attempt_score_policy' => AttemptScorePolicy::class,
        'time_limit_minutes' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_answers' => 'boolean',
        'show_correct_answers' => 'boolean',
        'show_score_immediately' => 'boolean',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'order_index' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'description',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return MorphTo<Model, self> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<QuizQuestion, self> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order_index');
    }

    /** @return HasMany<QuizAttempt, self> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** @return HasMany<AIContentJob, self> */
    public function aiContentJobs(): HasMany
    {
        return $this->hasMany(AIContentJob::class, 'target_id')
            ->where('target_type', AIContentTargetType::Quiz->value);
    }

    public function hasUnlimitedAttempts(): bool
    {
        return $this->max_attempts === 0;
    }

    public function hasTimeLimit(): bool
    {
        return $this->time_limit_minutes !== null && $this->time_limit_minutes > 0;
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from !== null && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until !== null && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    public function getTotalPoints(): float
    {
        return (float) $this->questions()->where('is_active', true)->sum('points');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', $now);
            });
    }
}
