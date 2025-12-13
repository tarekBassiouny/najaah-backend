<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property string $status
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

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

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
}
