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
 * @property int $video_id
 * @property int $course_id
 * @property int $center_id
 * @property int $enrollment_id
 * @property int|null $video_access_request_id
 * @property int $video_access_code_id
 * @property \Illuminate\Support\Carbon|null $granted_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property-read User $user
 * @property-read Video $video
 * @property-read Course $course
 * @property-read Center $center
 * @property-read Enrollment $enrollment
 * @property-read VideoAccessRequest|null $request
 * @property-read VideoAccessCode $code
 * @property-read User|null $revoker
 */
class VideoAccess extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'center_id',
        'enrollment_id',
        'video_access_request_id',
        'video_access_code_id',
        'granted_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
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

    /** @return BelongsTo<VideoAccessCode, self> */
    public function code(): BelongsTo
    {
        return $this->belongsTo(VideoAccessCode::class, 'video_access_code_id');
    }

    /** @return BelongsTo<User, self> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
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
    public function scopeForUserAndVideo(Builder $query, int $userId, int $videoId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('video_id', $videoId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->whereNull('deleted_at');
    }
}
