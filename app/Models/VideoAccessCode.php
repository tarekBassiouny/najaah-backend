<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoAccessCodeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $course_id
 * @property int $center_id
 * @property int $enrollment_id
 * @property int|null $video_access_request_id
 * @property string $code
 * @property VideoAccessCodeStatus $status
 * @property int|null $generated_by
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property-read User $user
 * @property-read Video $video
 * @property-read Course $course
 * @property-read Center $center
 * @property-read Enrollment $enrollment
 * @property-read VideoAccessRequest|null $request
 * @property-read User|null $generator
 * @property-read User|null $revoker
 * @property-read VideoAccess|null $access
 */
class VideoAccessCode extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_ACTIVE = VideoAccessCodeStatus::Active;

    public const STATUS_USED = VideoAccessCodeStatus::Used;

    public const STATUS_REVOKED = VideoAccessCodeStatus::Revoked;

    public const STATUS_EXPIRED = VideoAccessCodeStatus::Expired;

    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'center_id',
        'enrollment_id',
        'video_access_request_id',
        'code',
        'status',
        'generated_by',
        'generated_at',
        'used_at',
        'expires_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'status' => VideoAccessCodeStatus::class,
        'generated_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    /** @return BelongsTo<VideoAccessRequest, self> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(VideoAccessRequest::class, 'video_access_request_id');
    }

    /** @return BelongsTo<User, self> */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @return BelongsTo<User, self> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /** @return HasOne<VideoAccess, self> */
    public function access(): HasOne
    {
        return $this->hasOne(VideoAccess::class, 'video_access_code_id');
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_USED->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REVOKED->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUserAndVideo(Builder $query, User $user, Video $video): Builder
    {
        return $query->where('user_id', $user->id)
            ->where('video_id', $video->id);
    }
}
