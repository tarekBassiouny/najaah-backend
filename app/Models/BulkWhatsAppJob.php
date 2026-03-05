<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BulkJobStatus;
use App\Enums\WhatsAppCodeFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property int $total_codes
 * @property int $sent_count
 * @property int $failed_count
 * @property BulkJobStatus $status
 * @property WhatsAppCodeFormat $format
 * @property int $created_by
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property-read Center $center
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BulkWhatsAppJobItem> $items
 */
class BulkWhatsAppJob extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'total_codes',
        'sent_count',
        'failed_count',
        'status',
        'format',
        'started_at',
        'completed_at',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'status' => BulkJobStatus::class,
        'format' => WhatsAppCodeFormat::class,
        'settings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<BulkWhatsAppJobItem, self> */
    public function items(): HasMany
    {
        return $this->hasMany(BulkWhatsAppJobItem::class, 'bulk_job_id');
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
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BulkJobStatus::Pending->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', BulkJobStatus::Processing->value);
    }

    public function progressPercent(): int
    {
        if ($this->total_codes <= 0) {
            return 0;
        }

        return (int) floor((($this->sent_count + $this->failed_count) / $this->total_codes) * 100);
    }
}
