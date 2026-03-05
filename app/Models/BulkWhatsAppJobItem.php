<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BulkItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bulk_job_id
 * @property int $video_access_code_id
 * @property BulkItemStatus $status
 * @property string|null $error
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property int $attempts
 * @property-read BulkWhatsAppJob $bulkJob
 * @property-read VideoAccessCode $videoAccessCode
 */
class BulkWhatsAppJobItem extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    protected $fillable = [
        'bulk_job_id',
        'video_access_code_id',
        'status',
        'error',
        'sent_at',
        'attempts',
    ];

    protected $casts = [
        'status' => BulkItemStatus::class,
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /** @return BelongsTo<BulkWhatsAppJob, self> */
    public function bulkJob(): BelongsTo
    {
        return $this->belongsTo(BulkWhatsAppJob::class, 'bulk_job_id');
    }

    /** @return BelongsTo<VideoAccessCode, self> */
    public function videoAccessCode(): BelongsTo
    {
        return $this->belongsTo(VideoAccessCode::class, 'video_access_code_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BulkItemStatus::Pending->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', BulkItemStatus::Sent->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', BulkItemStatus::Failed->value);
    }
}
