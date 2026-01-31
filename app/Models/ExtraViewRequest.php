<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraViewRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $course_id
 * @property int $center_id
 * @property ExtraViewRequestStatus $status
 * @property string|null $reason
 * @property int|null $granted_views
 * @property string|null $decision_reason
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property-read User $user
 * @property-read Video $video
 * @property-read Course $course
 * @property-read Center $center
 * @property-read User|null $decider
 */
class ExtraViewRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ExtraViewRequestFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = ExtraViewRequestStatus::Pending;

    public const STATUS_APPROVED = ExtraViewRequestStatus::Approved;

    public const STATUS_REJECTED = ExtraViewRequestStatus::Rejected;

    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'center_id',
        'status',
        'reason',
        'granted_views',
        'decision_reason',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'granted_views' => 'integer',
        'decided_at' => 'datetime',
        'status' => ExtraViewRequestStatus::class,
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

    /** @return BelongsTo<User, self> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Scope to exclude soft-deleted requests.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to filter requests for a specific user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter requests for a specific video.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVideo(Builder $query, Video $video): Builder
    {
        return $query->where('video_id', $video->id);
    }

    /**
     * Scope to filter pending requests.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING->value);
    }

    /**
     * Scope to filter approved requests.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED->value);
    }

    /**
     * Scope to filter pending requests for a user and video.
     *
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

    /**
     * Scope to filter approved requests for a user and video.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApprovedForUserAndVideo(Builder $query, User $user, Video $video): Builder
    {
        return $query->forUser($user)
            ->forVideo($video)
            ->approved()
            ->notDeleted();
    }
}
