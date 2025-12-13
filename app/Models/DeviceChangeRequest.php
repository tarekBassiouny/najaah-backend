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
 * @property int|null $center_id
 * @property string $current_device_id
 * @property string $new_device_id
 * @property string $new_model
 * @property string $new_os_version
 * @property string $status
 * @property string|null $reason
 * @property string|null $decision_reason
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property-read User $user
 * @property-read Center|null $center
 * @property-read User|null $decider
 */
class DeviceChangeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceChangeRequestFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'user_id',
        'center_id',
        'current_device_id',
        'new_device_id',
        'new_model',
        'new_os_version',
        'status',
        'reason',
        'decision_reason',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
