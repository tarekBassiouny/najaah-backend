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
 * @property int|null $course_id
 * @property int|null $enrollment_id
 * @property int $device_id
 * @property string|null $embed_token
 * @property \Illuminate\Support\Carbon|null $embed_token_expires_at
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property int $progress_percent
 * @property bool $is_full_play
 * @property bool $auto_closed
 * @property bool $is_locked
 * @property int $watch_duration
 * @property string|null $close_reason
 * @property-read User $user
 * @property-read Video $video
 * @property-read Course|null $course
 * @property-read Enrollment|null $enrollment
 * @property-read UserDevice $device
 */
class PlaybackSession extends Model
{
    /** @use HasFactory<\Database\Factories\PlaybackSessionFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'enrollment_id',
        'device_id',
        'embed_token',
        'embed_token_expires_at',
        'started_at',
        'ended_at',
        'expires_at',
        'last_activity_at',
        'progress_percent',
        'is_full_play',
        'auto_closed',
        'is_locked',
        'watch_duration',
        'close_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'embed_token_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'progress_percent' => 'integer',
        'is_full_play' => 'boolean',
        'auto_closed' => 'boolean',
        'is_locked' => 'boolean',
        'watch_duration' => 'integer',
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

    /** @return BelongsTo<UserDevice, self> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Enrollment, self> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isEmbedTokenExpired(): bool
    {
        if ($this->embed_token_expires_at === null) {
            return true;
        }

        return $this->embed_token_expires_at->lte(now());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStale(Builder $query, int $seconds = 60): Builder
    {
        return $this->scopeActive($query)
            ->where('last_activity_at', '<', now()->subSeconds($seconds));
    }

    /**
     * Scope to filter sessions for a specific user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter expired sessions.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope to filter full plays for a specific user and video.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFullPlaysForUserAndVideo(Builder $query, User $user, Video $video): Builder
    {
        return $query->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('is_full_play', true);
    }
}
