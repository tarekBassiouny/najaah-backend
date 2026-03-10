<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $user_id
 * @property int $enrollment_id
 * @property int $center_id
 * @property int|null $group_id
 * @property SubmissionType $submission_type
 * @property string|null $text_content
 * @property string|null $link_url
 * @property SubmissionStatus $status
 * @property \Carbon\Carbon|null $submitted_at
 * @property bool $is_late
 * @property int $days_late
 * @property float|null $score
 * @property float|null $score_after_penalty
 * @property bool $passed
 * @property string|null $feedback
 * @property int|null $graded_by
 * @property \Carbon\Carbon|null $graded_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Assignment $assignment
 * @property-read User $user
 * @property-read Enrollment $enrollment
 * @property-read Center $center
 * @property-read AssignmentGroup|null $group
 * @property-read User|null $grader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssignmentSubmissionFile> $files
 */
class AssignmentSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentSubmissionFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'assignment_id',
        'user_id',
        'enrollment_id',
        'center_id',
        'group_id',
        'submission_type',
        'text_content',
        'link_url',
        'status',
        'submitted_at',
        'is_late',
        'days_late',
        'score',
        'score_after_penalty',
        'passed',
        'feedback',
        'graded_by',
        'graded_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'submission_type' => SubmissionType::class,
        'status' => SubmissionStatus::class,
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'days_late' => 'integer',
        'score' => 'decimal:2',
        'score_after_penalty' => 'decimal:2',
        'passed' => 'boolean',
        'graded_at' => 'datetime',
    ];

    /** @return BelongsTo<Assignment, self> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
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

    /** @return BelongsTo<AssignmentGroup, self> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AssignmentGroup::class);
    }

    /** @return BelongsTo<User, self> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /** @return HasMany<AssignmentSubmissionFile, self> */
    public function files(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionFile::class);
    }

    public function isDraft(): bool
    {
        return $this->status === SubmissionStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === SubmissionStatus::Submitted;
    }

    public function isGraded(): bool
    {
        return $this->status === SubmissionStatus::Graded;
    }

    public function isReturned(): bool
    {
        return $this->status === SubmissionStatus::Returned;
    }

    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    public function canBeGraded(): bool
    {
        return $this->status->canBeGraded();
    }

    public function isGroupSubmission(): bool
    {
        return $this->group_id !== null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Draft);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeGraded(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Graded);
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
    public function scopePendingGrading(Builder $query): Builder
    {
        return $query->where('status', SubmissionStatus::Submitted);
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
