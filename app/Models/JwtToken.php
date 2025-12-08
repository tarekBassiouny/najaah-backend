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
 * @property int|null $device_id
 * @property string $access_token
 * @property string $refresh_token
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $refresh_expires_at
 * @property \Carbon\Carbon|null $revoked_at
 * @property-read User $user
 * @property-read UserDevice|null $device
 */
class JwtToken extends Model
{
    /** @use HasFactory<\Database\Factories\JwtTokenFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'refresh_expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<UserDevice, self> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class);
    }
}
