<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\UserDeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 * @property string|null $device_name
 * @property DeviceType|null $device_type
 * @property string $model
 * @property string $os_version
 * @property int $status
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $last_used_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JwtToken> $tokens
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlaybackSession> $playbackSessions
 */
class UserDevice extends Model
{
    public const STATUS_ACTIVE = UserDeviceStatus::Active;

    public const STATUS_REVOKED = UserDeviceStatus::Revoked;

    public const STATUS_PENDING = UserDeviceStatus::Pending;

    /** @use HasFactory<\Database\Factories\UserDeviceFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'model',
        'os_version',
        'status',
        'approved_at',
        'last_used_at',
    ];

    protected $casts = [
        'status' => UserDeviceStatus::class,
        'approved_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function deviceTypeParsed(): ?DeviceType
    {
        return is_string($this->attributes['device_type'] ?? null)
            ? DeviceType::tryFrom($this->attributes['device_type'])
            : null;
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<JwtToken, self> */
    public function tokens(): HasMany
    {
        return $this->hasMany(JwtToken::class, 'device_id');
    }

    /** @return HasMany<PlaybackSession, self> */
    public function playbackSessions(): HasMany
    {
        return $this->hasMany(PlaybackSession::class, 'device_id');
    }

    /**
     * Scope to filter active devices.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE->value);
    }

    /**
     * Scope to exclude soft-deleted devices.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to filter devices for a specific user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter devices for a specific user id.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForUserId(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter active devices for a specific user.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveForUser(Builder $query, User $user): Builder
    {
        return $query->forUser($user)
            ->where('status', self::STATUS_ACTIVE->value);
    }

    /**
     * Scope to filter web devices.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWeb(Builder $query): Builder
    {
        return $query->where('device_type', DeviceType::Web);
    }

    /**
     * Scope to filter mobile devices.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('device_type', DeviceType::Mobile);
    }
}
