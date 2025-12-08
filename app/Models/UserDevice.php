<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $device_id
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
    /** @use HasFactory<\Database\Factories\UserDeviceFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_id',
        'model',
        'os_version',
        'status',
        'approved_at',
        'last_used_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'approved_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

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
}
