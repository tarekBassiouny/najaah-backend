<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property int $status
 * @property \Illuminate\Support\Carbon $enrolled_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read User $user
 * @property-read Course $course
 * @property-read Center $center
 */
class Enrollment extends Model
{
    public const STATUS_ACTIVE = 0;

    public const STATUS_DEACTIVATED = 1;

    public const STATUS_CANCELLED = 2;

    public const STATUS_PENDING = 3;

    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'center_id',
        'status',
        'enrolled_at',
        'expires_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => 'integer',
    ];

    /** @return array<int, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'ACTIVE',
            self::STATUS_DEACTIVATED => 'DEACTIVATED',
            self::STATUS_CANCELLED => 'CANCELLED',
            self::STATUS_PENDING => 'PENDING',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? 'UNKNOWN';
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
        return $this->belongsTo(Center::class);
    }

    /**
     * Scope to filter active enrollments for a specific user and course.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveForUserAndCourse(Builder $query, User $user, Course $course): Builder
    {
        return $query->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter active enrollments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter pending enrollments.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
