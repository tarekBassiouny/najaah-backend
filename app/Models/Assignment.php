<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionType;
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
 * @property array<int> $submission_types
 * @property array<string>|null $allowed_file_types
 * @property int $max_file_size_mb
 * @property int $max_files
 * @property bool $is_group_assignment
 * @property int|null $max_group_size
 * @property float $max_points
 * @property float $passing_score
 * @property bool $is_required
 * @property bool $is_active
 * @property \Carbon\Carbon|null $due_date
 * @property bool $late_submission_allowed
 * @property float $late_penalty_percent
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssignmentSubmission> $submissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssignmentGroup> $groups
 */
class Assignment extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentFactory> */
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
        'submission_types',
        'allowed_file_types',
        'max_file_size_mb',
        'max_files',
        'is_group_assignment',
        'max_group_size',
        'max_points',
        'passing_score',
        'is_required',
        'is_active',
        'due_date',
        'late_submission_allowed',
        'late_penalty_percent',
        'available_from',
        'available_until',
        'order_index',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'submission_types' => 'array',
        'allowed_file_types' => 'array',
        'max_file_size_mb' => 'integer',
        'max_files' => 'integer',
        'is_group_assignment' => 'boolean',
        'max_group_size' => 'integer',
        'max_points' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'due_date' => 'datetime',
        'late_submission_allowed' => 'boolean',
        'late_penalty_percent' => 'decimal:2',
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

    /** @return HasMany<AssignmentSubmission, self> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /** @return HasMany<AssignmentGroup, self> */
    public function groups(): HasMany
    {
        return $this->hasMany(AssignmentGroup::class);
    }

    public function allowsSubmissionType(SubmissionType $type): bool
    {
        return in_array($type->value, $this->submission_types, true);
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

    public function isPastDue(): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        return now()->gt($this->due_date);
    }

    public function canSubmitLate(): bool
    {
        return $this->late_submission_allowed && $this->isPastDue();
    }

    public function calculateLatePenalty(int $daysLate): float
    {
        if ($daysLate <= 0 || ! $this->late_submission_allowed) {
            return 0.0;
        }

        return min(100.0, (float) $daysLate * (float) $this->late_penalty_percent);
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
