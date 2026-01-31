<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceChangeRequestSource;
use App\Enums\DeviceChangeRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $center_id
 * @property string|null $current_device_id
 * @property string $new_device_id
 * @property string $new_model
 * @property string $new_os_version
 * @property DeviceChangeRequestStatus $status
 * @property DeviceChangeRequestSource $request_source
 * @property \Illuminate\Support\Carbon|null $otp_verified_at
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

    public const STATUS_PENDING = DeviceChangeRequestStatus::Pending;

    public const STATUS_APPROVED = DeviceChangeRequestStatus::Approved;

    public const STATUS_REJECTED = DeviceChangeRequestStatus::Rejected;

    public const STATUS_PRE_APPROVED = DeviceChangeRequestStatus::PreApproved;

    public const SOURCE_MOBILE = DeviceChangeRequestSource::Mobile;

    public const SOURCE_OTP = DeviceChangeRequestSource::Otp;

    public const SOURCE_ADMIN = DeviceChangeRequestSource::Admin;

    protected $fillable = [
        'user_id',
        'center_id',
        'current_device_id',
        'new_device_id',
        'new_model',
        'new_os_version',
        'status',
        'request_source',
        'otp_verified_at',
        'reason',
        'decision_reason',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'status' => DeviceChangeRequestStatus::class,
        'request_source' => DeviceChangeRequestSource::class,
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
     * Scope to filter pre-approved requests.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePreApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PRE_APPROVED->value);
    }

    /**
     * Scope to filter pending or pre-approved requests.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendingOrPreApproved(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING->value, self::STATUS_PRE_APPROVED->value]);
    }
}
