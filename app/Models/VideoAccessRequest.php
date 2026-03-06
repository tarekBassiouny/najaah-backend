<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoAccessRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $course_id
 * @property int $center_id
 * @property int $enrollment_id
 * @property VideoAccessRequestStatus $status
 * @property string|null $reason
 * @property string|null $decision_reason
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property-read User $user
 * @property-read Video $video
 * @property-read Course $course
 * @property-read Center $center
 * @property-read Enrollment $enrollment
 * @property-read User|null $decider
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VideoAccessCode> $codes
 */
class VideoAccessRequest extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = VideoAccessRequestStatus::Pending;

    public const STATUS_APPROVED = VideoAccessRequestStatus::Approved;

    public const STATUS_REJECTED = VideoAccessRequestStatus::Rejected;

    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'center_id',
        'enrollment_id',
        'status',
        'reason',
        'decision_reason',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'status' => VideoAccessRequestStatus::class,
        'decided_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Video, self> */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
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

    /** @return BelongsTo<Enrollment, self> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<User, self> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return HasMany<VideoAccessCode, self> */
    public function codes(): HasMany
    {
        return $this->hasMany(VideoAccessCode::class, 'video_access_request_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVideo(Builder $query, Video $video): Builder
    {
        return $query->where('video_id', $video->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendingForUserAndVideo(Builder $query, User $user, Video $video): Builder
    {
        return $query->forUser($user)
            ->forVideo($video)
            ->pending()
            ->notDeleted();
    }
}
