<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Observers\EnrollmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property int $center_id
 * @property string|null $reason
 * @property EnrollmentStatus $status
 * @property \Illuminate\Support\Carbon $enrolled_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read User $user
 * @property-read Course $course
 * @property-read Center $center
 */
#[ObservedBy(EnrollmentObserver::class)]
class Enrollment extends Model
{
    public const STATUS_ACTIVE = EnrollmentStatus::Active;

    public const STATUS_DEACTIVATED = EnrollmentStatus::Deactivated;

    public const STATUS_CANCELLED = EnrollmentStatus::Cancelled;

    public const STATUS_PENDING = EnrollmentStatus::Pending;

    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'center_id',
        'reason',
        'status',
        'enrolled_at',
        'expires_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => EnrollmentStatus::class,
    ];

    /** @return array<int, string> */
    public static function statusLabels(): array
    {
        return [
            0 => 'ACTIVE',
            1 => 'DEACTIVATED',
            2 => 'CANCELLED',
            3 => 'PENDING',
        ];
    }

    public function statusLabel(): string
    {
        if ($this->status instanceof EnrollmentStatus) {
            return match ($this->status) {
                EnrollmentStatus::Active => 'ACTIVE',
                EnrollmentStatus::Deactivated => 'DEACTIVATED',
                EnrollmentStatus::Cancelled => 'CANCELLED',
                EnrollmentStatus::Pending => 'PENDING',
            };
        }

        return self::statusLabels()[(int) $this->status] ?? 'UNKNOWN';
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class)->withTrashed();
    }

    /**
     * Scope to exclude soft-deleted enrollments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to filter enrollments for a specific user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter enrollments for a specific course.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCourse(Builder $query, Course $course): Builder
    {
        return $query->where('course_id', $course->id);
    }

    /**
     * Scope to filter enrollments for a specific user and course.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUserAndCourse(Builder $query, User $user, Course $course): Builder
    {
        return $query->forUser($user)
            ->forCourse($course);
    }

    /**
     * Scope to filter active enrollments for a specific user and course.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveForUserAndCourse(Builder $query, User $user, Course $course): Builder
    {
        return $query->forUserAndCourse($user, $course)
            ->where('status', self::STATUS_ACTIVE->value)
            ->notDeleted();
    }

    /**
     * Scope to filter active enrollments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE->value);
    }

    /**
     * Scope to filter pending enrollments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING->value);
    }

    /**
     * Scope to filter pending enrollments for a specific user and course.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendingForUserAndCourse(Builder $query, User $user, Course $course): Builder
    {
        return $query->forUserAndCourse($user, $course)
            ->where('status', self::STATUS_PENDING->value)
            ->notDeleted();
    }
}
