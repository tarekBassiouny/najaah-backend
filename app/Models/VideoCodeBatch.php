<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoCodeBatchStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $video_id
 * @property int $course_id
 * @property int $center_id
 * @property string $batch_code
 * @property string $secret_key
 * @property int $quantity
 * @property int|null $sold_limit
 * @property int $redeemed_count
 * @property int $view_limit_per_code
 * @property VideoCodeBatchStatus $status
 * @property int $generated_by
 * @property \Illuminate\Support\Carbon $generated_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property int|null $closed_by
 * @property array<string, mixed>|null $metadata
 * @property-read Video $video
 * @property-read Course $course
 * @property-read Center $center
 * @property-read User $generator
 * @property-read User|null $closer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VideoCodeRedemption> $redemptions
 */
class VideoCodeBatch extends Model
{
    /** @use HasFactory<\Database\Factories\VideoCodeBatchFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'video_id',
        'course_id',
        'center_id',
        'batch_code',
        'secret_key',
        'quantity',
        'sold_limit',
        'redeemed_count',
        'view_limit_per_code',
        'status',
        'generated_by',
        'generated_at',
        'closed_at',
        'closed_by',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sold_limit' => 'integer',
        'redeemed_count' => 'integer',
        'view_limit_per_code' => 'integer',
        'status' => VideoCodeBatchStatus::class,
        'generated_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

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
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @return BelongsTo<User, self> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<VideoCodeRedemption, self> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VideoCodeRedemption::class, 'batch_id');
    }

    public function isOpen(): bool
    {
        return $this->status === VideoCodeBatchStatus::Open;
    }

    public function isClosed(): bool
    {
        return $this->status === VideoCodeBatchStatus::Closed;
    }

    /**
     * Check if redemptions are allowed for this batch.
     * When open: always allow (no sold_limit check)
     * When closed: check if redeemed_count < sold_limit
     */
    public function canRedeem(): bool
    {
        if ($this->isOpen()) {
            return true;
        }

        if ($this->sold_limit === null) {
            return false;
        }

        return $this->redeemed_count < $this->sold_limit;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', VideoCodeBatchStatus::Open->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', VideoCodeBatchStatus::Closed->value);
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
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVideo(Builder $query, int $videoId): Builder
    {
        return $query->where('video_id', $videoId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCourseVideo(Builder $query, int $courseId, int $videoId): Builder
    {
        return $query->forCourse($courseId)->forVideo($videoId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }
}
