<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $batch_id
 * @property int $sequence_number
 * @property int $user_id
 * @property int|null $video_access_id
 * @property \Illuminate\Support\Carbon $redeemed_at
 * @property-read VideoCodeBatch $batch
 * @property-read User $user
 * @property-read VideoAccess|null $videoAccess
 */
class VideoCodeRedemption extends Model
{
    /** @use HasFactory<\Database\Factories\VideoCodeRedemptionFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'sequence_number',
        'user_id',
        'video_access_id',
        'redeemed_at',
    ];

    protected $casts = [
        'batch_id' => 'integer',
        'sequence_number' => 'integer',
        'user_id' => 'integer',
        'video_access_id' => 'integer',
        'redeemed_at' => 'datetime',
    ];

    /** @return BelongsTo<VideoCodeBatch, self> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(VideoCodeBatch::class, 'batch_id');
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<VideoAccess, self> */
    public function videoAccess(): BelongsTo
    {
        return $this->belongsTo(VideoAccess::class, 'video_access_id');
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
    public function scopeForBatch(Builder $query, int $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }
}
